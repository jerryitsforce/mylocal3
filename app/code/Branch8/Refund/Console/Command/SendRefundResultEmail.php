<?php

namespace Branch8\Refund\Console\Command;

use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\Email;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: send a refund result email using {@see Email}.
 */
class SendRefundResultEmail extends Command
{
    public const EMAIL_TEMPLATE_ID = 'emailTemplateId';
    public const RECEIVERS = 'receivers';
    public const EMAILVARS = 'emailVars';

    private const LOG_CLASS_KEY = 'SendRefundResultEmail';

    /** @var State */
    private $state;

    /** @var Email */
    protected $email;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param State $state Application state
     * @param Email $email Refund mail helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        State $state,
        Email $email,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->state = $state;
        $this->email = $email;
        $this->refundLogger = $refundLogger;
        parent::__construct();
    }

    /**
     * Declares template id, receivers and optional template vars.
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::EMAIL_TEMPLATE_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'email template id'
            ),
            new InputOption(
                self::RECEIVERS,
                null,
                InputOption::VALUE_REQUIRED,
                'receivers'
            ),
            new InputOption(
                self::EMAILVARS,
                null,
                InputOption::VALUE_OPTIONAL,
                'email vars'
            ),
        ];
        $this->setName('refund:send:mail')
            ->setDescription('Send Refund Result Email')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Send email from CLI options.
     *
     * @param InputInterface $input Console input
     * @param OutputInterface $output Console output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $emailTemplateId = $input->getOption(self::EMAIL_TEMPLATE_ID);
        $receivers = $input->getOption(self::RECEIVERS);
        $emailVars = $input->getOption(self::EMAILVARS) ?? [];

        try {
            $this->email->setEmailTemplateId($emailTemplateId);
            $this->email->setEmailReceivers($receivers);
            $this->email->setEmailVars($emailVars);

            $this->email->send();
        } catch (\Throwable $th) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $th, 'refund:send:mail');
            $message = 'Exception message: ' . $th->getMessage();

            throw new \Exception($message);
        }

        return self::SUCCESS;
    }
}
