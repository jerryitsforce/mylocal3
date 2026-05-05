<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/03/2026
 */

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Branch8\MarketPlaceParentOrder\Exception\InvalidArgumentException;
use RuntimeException;

class SubsetFontGenerator
{
    const BOLD = 'Bold';
    const ITALIC = 'Italic';
    const REGULAR = 'Regular';

    private const FILENAMES = [
        self::BOLD => 'extra-font/NotoSans/NotoSansCJKtc-Bold.ttf',
        self::REGULAR => 'extra-font/NotoSans/NotoSansCJKtc-Regular.ttf',
        self::ITALIC => 'extra-font/NotoSans/NotoSansCJKtc-Italic.ttf',
    ];

    const ALL = [self::BOLD, self::ITALIC, self::REGULAR];

    private string $pyftsubsetBin;
    private bool $hinting;
    private bool $ignoreMissingGlyphs;
    private array $layoutFeatures;
    private DirectoryList $directoryList;
    /**
     * @var string
     */
    protected string $cacheDir;
    /**
     * @var string
     */
    protected string $asciiFallback =
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" .
        " .,;:-_()[]{}#%&@!?/\\'\"+=*";

    protected array $texts = [];
    /**
     * @var
     */
    private $parentOrder;
    /**
     * @var array
     */
    private $extractors;


    /**
     * @var array
     */
    private array $originFontPaths = [];
    /**
     * @var array
     */
    private $subsetFontPaths = [];

    /**
     * @param DirectoryList $directoryList
     * @param string $pyftsubsetBin
     * @param bool $hinting
     * @param bool $ignoreMissingGlyphs
     * @param array $layoutFeatures
     * @param array $extractors
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        DirectoryList $directoryList,
        string        $pyftsubsetBin='extra_bin/pyftsubset',
        bool          $hinting = false,
        bool          $ignoreMissingGlyphs = true,
        array         $layoutFeatures = ['*'],
        array         $extractors = []
    )
    {
        $this->extractors = $extractors;
        $this->directoryList = $directoryList;
        $this->cacheDir = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR) . '/font_subset', '/');
        $this->pyftsubsetBin = $this->directoryList->getPath(DirectoryList::ROOT) . '/' . $pyftsubsetBin;
        $this->hinting = $hinting;
        $this->ignoreMissingGlyphs = $ignoreMissingGlyphs;
        $this->layoutFeatures = $layoutFeatures;
        foreach (self::FILENAMES as $type => $path) {
            $this->originFontPaths[$type] = $this->directoryList->getPath(DirectoryList::ROOT) . '/' . $path;
        }
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    public function commandExists(): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($this->pyftsubsetBin)));
        return !empty($return);
    }

    /**
     * @param ParentOrder $parentOrder
     * @return $this
     */
    public function setParentOrder(ParentOrder $parentOrder)
    {
        $this->parentOrder = $parentOrder;
        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->texts = [];
        foreach ($this->subsetFontPaths as $type => $path) {
          //  @unlink($path);
        }
        return $this;
    }

    /**
     * @param string $text
     * @return $this|void
     */
    public function addText(string $text)
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }
        if ($text !== '') {
            $this->texts[] = $text;
        }
        return $this;
    }

    /**
     * Create a subset of a single font variant.
     *
     * @param string $variant self::BOLD | ITALIC | REGULAR (case-insensitive).
     * @param string $characters All characters that must appear in the subset.
     * @param string $outputPath Destination file path for the subsetted font.
     * @param int[] $extraUnicodes Additional Unicode code-points (as integers) to include.
     *
     * @return string  Resolved absolute path of the generated file.
     *
     * @throws RuntimeException  On subsetting failure.
     */
    public function subset(
        string $variant,
        string $characters,
        string $outputPath,
        array  $extraUnicodes = []
    ): string
    {
        $variant = $this->normaliseVariant($variant);
        $sourcePath = $this->originFontPaths[$variant];
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $uniCodes = $this->buildUnicodeList($characters, $extraUnicodes);
        $unicodeArg = implode(',', array_map(fn(int $cp) => 'U+' . strtoupper(dechex($cp)), $uniCodes));
        $this->log("info", "[{$variant}] Subsetting " . count($uniCodes) . " code-points → {$outputPath}");
        $cmd = $this->buildCommand($sourcePath, $outputPath, $unicodeArg);
        $this->runCommand($cmd);
        $originalKb = round(filesize($sourcePath) / 1024, 1);
        $subsetKb = round(filesize($outputPath) / 1024, 1);
        $reduction = $originalKb > 0 ? round(100 * (1 - $subsetKb / $originalKb)) : 0;
        $this->log("info", "[{$variant}] Done. {$originalKb} KB → {$subsetKb} KB ({$reduction}% reduction)");
        return realpath($outputPath);
    }

    /**
     * Normalise and validate a variant string.
     *
     * @throws InvalidArgumentException
     */
    private function normaliseVariant(string $variant): string
    {
        $normalised = ucfirst(strtolower(trim($variant)));
        if (!in_array($normalised, self::ALL, true)) {
            throw new InvalidArgumentException(
                __("Unknown variant '{$variant}'. Choose from: " . implode(', ', self::ALL))
            );
        }
        return $normalised;
    }

    /**
     * Subset all three variants with the same character set.
     *
     * @param string $characters Characters to include in every variant.
     * @param string $outputDir Directory where the three subset files are saved.
     * @param string $suffix Appended before the file extension (default: '-subset').
     * @param int[] $extraUnicodes Additional code-points for every variant.
     *
     * @return array<string,string>   Map of variant name → absolute output path.
     */
    public function subsetAll(
        string $characters,
        string $suffix = '-subset',
        array  $extraUnicodes = []
    ): array
    {
        $name = hash('sha256', $characters);
        foreach (self::ALL as $variant) {
            $outputPath = rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "{$name}{$variant}{$suffix}.ttf";
            if (file_exists($outputPath)) {
                $this->subsetFontPaths[$variant] = $outputPath;
                continue;
            }
            $this->subsetFontPaths[$variant] = $this->subset($variant, $characters, $outputPath, $extraUnicodes);
        }
        return $this->subsetFontPaths;
    }

    /**
     * Generate subset font based on text
     */
    public function generate(): array
    {
        /**
         * @var $extractor ParentOrder\Pdf\SubsetFont\TextExtractorInterface
         */
        foreach ($this->extractors as $extractor) {
            $this->texts = array_merge($this->texts, $extractor->extract($this->parentOrder));
        }
        $this->subsetAll(join('', $this->texts));
        return $this->subsetFontPaths;
    }

    /**
     * @param string $characters
     * @param array $extraUnicodes
     * @return array
     */
    private function buildUnicodeList(string $characters, array $extraUnicodes = []): array
    {
        $codePoints = [];
        // mb_str_split handles multi-byte characters correctly
        foreach (mb_str_split($characters) as $char) {
            if (trim($char) === '') {
                continue; // skip whitespace
            }
            $cp = mb_ord($char, 'UTF-8');
            if ($cp !== false) {
                $codePoints[$cp] = true;
            }
        }
        foreach ($extraUnicodes as $cp) {
            $codePoints[(int)$cp] = true;
        }
        $result = array_keys($codePoints);
        sort($result);
        return $result;
    }

    /**
     * Build the pyftsubset shell command.
     * @param string $source
     * @param string $output
     * @param string $unicodeArg
     * @return string
     */
    private function buildCommand(string $source, string $output, string $unicodeArg): string
    {
        $parts = [
            escapeshellcmd($this->pyftsubsetBin),
            escapeshellarg($source),
            escapeshellarg("--unicodes={$unicodeArg}"),
            escapeshellarg("--output-file={$output}"),
            escapeshellarg('--layout-features=' . implode(',', $this->layoutFeatures)),
        ];

        if (!$this->hinting) {
            $parts[] = '--no-hinting';
            $parts[] = '--desubroutinize';
        }

        if ($this->ignoreMissingGlyphs) {
            $parts[] = '--ignore-missing-glyphs';
        }
        return implode(' ', $parts);
    }

    /**
     * Execute a shell command, throw on non-zero exit.
     *
     * @throws RuntimeException
     */
    private function runCommand(string $cmd): void
    {
        $this->log('debug', "Running: {$cmd}");

        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                "pyftsubset failed (exit {$exitCode}):\n" . implode("\n", $output)
            );
        }
    }

    /**
     * Simple PSR-3-style logger (writes to STDERR or error_log).
     */
    private function log(string $level, string $message): void
    {
        $prefix = strtoupper($level);
        $line = "[{$prefix}] {$message}";

        if (defined('STDERR')) {
            fwrite(STDERR, $line . PHP_EOL);
        } else {
            error_log($line);
        }
    }

    /**
     * @param $type
     * @return string
     */
    public function getSubsetFontPath($type): string
    {
        return $this->subsetFontPaths[$type];
    }
}
