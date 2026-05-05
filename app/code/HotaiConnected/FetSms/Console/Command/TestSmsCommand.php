<?php
namespace HotaiConnected\FetSms\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use HotaiConnected\FetSms\Api\SmsSenderInterface;

class TestSmsCommand extends Command
{
    /**
     * @var SmsSenderInterface
     */
    protected $smsSender;

    /**
     * @param SmsSenderInterface $smsSender
     */
    public function __construct(SmsSenderInterface $smsSender)
    {
        $this->smsSender = $smsSender;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fet:sms:test')
            ->setDescription('測試 FET 簡訊發送功能')
            ->addArgument('phone', InputArgument::REQUIRED, '接收手機號碼 (例如: 886912345678)')
            ->addArgument('content', InputArgument::REQUIRED, '簡訊內容')
            ->addArgument('callname', InputArgument::REQUIRED, '呼叫者名稱 (例如: Test_Module)');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phone = $input->getArgument('phone');
        $content = $input->getArgument('content');
        $callname = $input->getArgument('callname');

        $output->writeln("<info>正在發送簡訊至: $phone ...</info>");
        $output->writeln("<info>內容: $content</info>");
        $output->writeln("<info>識別名稱: $callname</info>");

        $result = $this->smsSender->send($phone, $content, $callname);

        if ($result['success']) {
            $output->writeln("<info>發送成功！</info>");
        } else {
            $output->writeln("<error>發送失敗！</error>");
        }

        $output->writeln("回應代碼: " . $result['code']);
        $output->writeln("回應文字: " . $result['text']);
        $output->writeln("訊息 ID: " . $result['msgId']);

        return Command::SUCCESS;
    }
}
