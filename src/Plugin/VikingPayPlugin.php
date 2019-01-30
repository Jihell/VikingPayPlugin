<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayBundle\Plugin;

use Jihel\VikingPayBundle\Adapter\VikingPayAdapter;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException;

/**
 * Class VikingPayPlugin
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class VikingPayPlugin extends AbstractPlugin
{
    /**
     * @var VikingPayAdapter
     */
    protected $adapter;

    /**
     * @param VikingPayAdapter $adapter
     */
    public function setAdapter(VikingPayAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param                               $retry
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        if (!$transaction->getExtendedData()->has('token')) {
            $this->adapter->registration($transaction);
        }
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param                               $retry
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        if (!$transaction->getExtendedData()->has('token')) {
            $this->adapter->registration($transaction);
        }

        $this->adapter->deposit($transaction);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param                               $retry
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->adapter->deposit($transaction);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param                               $retry
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    public function reverseDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->adapter->refund($transaction);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @param                               $retry
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        // Is not independant credit, it's a refund
        if ($transaction->getCredit()->getPayment()) {
            return $this->adapter->refund($transaction);
        }

        throw new FunctionNotSupportedException('credit() is not supported by this plugin.');
    }

    /**
     * @param PaymentInstructionInterface $instruction
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $errorBuilder = new ErrorBuilder();
        $data = $instruction->getExtendedData();

        if (!$data->get('holder')) {
            $errorBuilder->addDataError('holder', 'form.error.required');
        }
        if (!$data->get('number')) {
            $errorBuilder->addDataError('number', 'form.error.required');
        }
        if (!$data->get('expire_month')) {
            $errorBuilder->addDataError('number', 'form.error.required');
        }
        if (!$data->get('expire_year')) {
            $errorBuilder->addDataError('number', 'form.error.required');
        }
        if (!$data->get('code')) {
            $errorBuilder->addDataError('number', 'form.error.required');
        }

        if ($instruction->getAmount() > 10000) {
            $errorBuilder->addGlobalError('form.error.credit_card_max_limit_exceeded');
        }

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function processes($name)
    {
        return 'jihel_viking_pay_credit_card' === $name;
    }
}
