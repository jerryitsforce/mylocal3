<?php
namespace HotaiConnected\Image\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface as Logger;

class CheckExistenceCommand extends Command
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     * 
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection, Logger $logger)
    {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('image:check:existence')
            ->setDescription('Check if images exist, check their size and compare with placeholder image size');
            
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Placeholder image URL (this will be the reference for size comparison)
        $placeholderImageUrl = 'https://mcprod.hotaigo.com.tw/static/version1735805968/frontend/Branch8/hotai/zh_Hant_TW/Magento_Catalog/images/product/placeholder/image.jpg';
        // Get the file size of the placeholder image via cURL
        $placeholderFileSize = $this->getRemoteFileSize($placeholderImageUrl);
        if ($placeholderFileSize === false) {
            $output->writeln('<info>get image size fail:</info>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->writeln('<info>current path:</info>' . $placeholderFileSize);
        // Using the ResourceConnection to get the database connection
        $connection = $this->resourceConnection->getConnection();

        // Initialize variables for pagination
        $batchSize = 500;  // How many records to fetch per query
        $offset = 0;       // Start from the first record

        // Directory base path for checking file existence
        $baseDirectory = BP . '/pub/media/catalog/product';
        $output->writeln('<info>current path:</info>' . $baseDirectory);
        // Loop to fetch images in batches and check if the image exists
        do {
            try {
                // Correct SQL query for LIMIT and OFFSET with direct insertion
                $query = 'SELECT distinct(value) FROM catalog_product_entity_media_gallery LIMIT ' . (int)$batchSize . ' OFFSET ' . (int)$offset;
                
                // Execute the query
                $result = $connection->fetchAll($query);

                if (empty($result)) {
                    // If no records are returned, break the loop
                    break;
                }

                // Process each result (image path)
                foreach ($result as $row) {
                    $imagePath = $baseDirectory . $row['value']; // Construct the full file path

                    // Check if the file exists
                    if (!file_exists($imagePath)) {
                        // Log to existence log if the file doesn't exist and continue to next image
                        $this->logger->info('image_not_found: ' . $imagePath);
                        continue; // Skip the current iteration and proceed to the next image
                    }

                    // Check if the file size is 0
                    $fileSize = filesize($imagePath);
                    if ($fileSize === 0) {
                        // Log to size 0 log if the file size is 0 and continue to next image
                        $this->logger->info('image_size_0: ' . $imagePath);
                        continue; // Skip the current iteration and proceed to the next image
                    }

                    // Compare the image file size with the placeholder file size
                    if ($fileSize == $placeholderFileSize) {
                        // If file sizes are equal, log it in the default check log
                        $this->logger->info('image_match_default: ' . $imagePath);
                    }
                    usleep(100000);
                }

                // Increase the offset for the next batch
                $offset += $batchSize;
                $this->logger->info('<info>current counter:</info>' . $offset);
                $this->logger->info('offset counter:' . $offset);

            } catch (\Exception $e) {
                // Log any errors during the query execution
                $this->logger->error('Database query failed: ' . $e->getMessage());
                break;
            }

        } while (true); // Continue fetching in batches until all records are processed

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Get file size of a remote file using cURL
     *
     * @param string $url
     * @return int|false
     */
    private function getRemoteFileSize($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);  // We only need the headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow redirects if needed

        // Execute cURL request
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return false;
        }

        // Get the content length from headers
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        curl_close($ch);

        // Return the file size or false if not found
        return $contentLength > 0 ? $contentLength : false;
    }
}