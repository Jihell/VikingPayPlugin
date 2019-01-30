<?php
/**
 * @licence Proprietary
 */
namespace Jihel\VikingPayBundle\Adapter;

use GuzzleHttp\Client;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VikingPayAdapter
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class VikingPayAdapter
{
    const BASE_URL_SANDBOX = 'https://test.oppwa.com/v1/';
    const BASE_URL_PROD = 'https://oppwa.com/v1/';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $accounts;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Client
     */
    protected $client;

    /**
     * VikingPayAdapter constructor.
     *
     * @param array  $config
     * @param array  $accounts
     * @param string $environment
     */
    public function __construct(
        array $config,
        array $accounts,
        string $environment
    ) {
        $this->config = $config;
        $this->accounts = $accounts;
        $this->environment = $environment;
    }

    /**
     * Create client singleton
     *
     * @return Client
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $env = ($this->environment !== 'prod' ? 'SANDBOX' : 'PROD');

            $this->client = new Client(array_merge($this->config, [
                'base_uri' => constant(sprintf('static::BASE_URL_%s', $env)),
            ]));
        }

        return $this->client;
    }

    /***
     * @param string $mid
     * @return array
     */
    protected function getInitialQueryParameters(string $mid)
    {
        return [
            'authentication.userId' => $this->accounts[$mid]['userId'],
            'authentication.password' => $this->accounts[$mid]['password'],
            'authentication.entityId' => $this->accounts[$mid]['entityId'],
            'paymentBrand' => 'CARTEBANCAIRE',
        ];
    }

    /**
     * Setup a payment agreement
     *
     * @param FinancialTransactionInterface $transaction
     * @return FinancialTransactionInterface
     * @throws FinancialException
     */
    public function registration(FinancialTransactionInterface $transaction)
    {
        $url = 'registrations';
        $data = $this->getInitialQueryParameters($transaction->getExtendedData()->get('mid'));

        $data['card.holder'] = $transaction->getExtendedData()->get('holder');
        $data['card.number'] = $transaction->getExtendedData()->get('number');
        $data['card.expiryMonth'] = str_pad($transaction->getExtendedData()->get('expire_month'), 2, '0', STR_PAD_LEFT);
        $data['card.expiryYear'] = $transaction->getExtendedData()->get('expire_year');
        $data['card.cvv'] = $transaction->getExtendedData()->get('code');

        try {
            $response = $this->getClient()->post($url, [
                'query' => $data,
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

            if(Response::HTTP_OK === $response->getStatusCode()) {
                $transaction->getExtendedData()->set('token', $content['id']);
                $transaction->setTrackingId($content['ndc']);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
            }
        } catch (\Exception $e) {
            $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);

            $ex = new FinancialException(sprintf('Payment refused, exception occured "%s"', $e->getMessage()), null, $e);
            $ex->setFinancialTransaction($transaction);

            throw $ex;
        }

        return $transaction;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return FinancialTransactionInterface
     * @throws FinancialException
     */
    public function deposit(FinancialTransactionInterface $transaction)
    {
        $url = sprintf('registrations/%s/payments', $this->getToken($transaction));

        $data = $this->getInitialQueryParameters($transaction->getExtendedData()->get('mid'));
        $data['amount'] = $transaction->getRequestedAmount();
        $data['currency'] = 'EUR';
        $data['paymentType'] = 'DB';
        $data['recurringType'] = 'REPEATED';

        try {
            $response = $this->getClient()->post($url, [
                'query' => $data,
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);

            $ex = new FinancialException(sprintf('Payment refused, exception occured "%s"', $e->getMessage()), null, $e);
            $ex->setFinancialTransaction($transaction);
            throw $ex;
        }

        if(Response::HTTP_OK === $response->getStatusCode()) {
            $transaction->setTrackingId($content['ndc']);
            if (0 === strpos($content['result']['code'], '000')) {
                $transaction->setReferenceNumber($content['id']);
                $transaction->setProcessedAmount($content['amount']);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $ex = new FinancialException('Payment refused.');
                $ex->setFinancialTransaction($transaction);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                throw $ex;
            }
        } else {
            $ex = new FinancialException(sprintf('Invalid response %d', $response->getStatusCode()));
            $ex->setFinancialTransaction($transaction);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);

            throw $ex;
        }


        return $transaction;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return FinancialTransactionInterface
     * @throws FinancialException
     */
    public function refund(FinancialTransactionInterface $transaction)
    {
        $payment = $transaction->getPayment() ?: $transaction->getCredit()->getPayment();
        $referenceNumber = $payment->getApproveTransaction()->getReferenceNumber();

        $url = sprintf('payments/%s', $referenceNumber);

        $data = $this->getInitialQueryParameters($transaction->getExtendedData()->get('mid'));
        $data['amount'] = $transaction->getRequestedAmount();
        $data['currency'] = 'EUR';
        $data['paymentType'] = 'RF';

        try {
            $response = $this->getClient()->post($url, [
                'query' => $data,
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);

            $ex = new FinancialException(sprintf('Refund refused, exception occured "%s"', $e->getMessage()), null, $e);
            $ex->setFinancialTransaction($transaction);
            throw $ex;
        }

        if(Response::HTTP_OK === $response->getStatusCode()) {
            $transaction->setTrackingId($content['ndc']);

            if (0 === strpos($content['result']['code'], '000')) {
                $transaction->setProcessedAmount($content['amount']);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $ex = new FinancialException('Refund refused.');
                $ex->setFinancialTransaction($transaction);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                throw $ex;
            }
        } else {
            $ex = new FinancialException(sprintf('Invalid response %d', $response->getStatusCode()));
            $ex->setFinancialTransaction($transaction);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_INVALID);

            throw $ex;
        }

        return $transaction;
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return string|null
     */
    protected function getToken(FinancialTransactionInterface $transaction)
    {
        return $transaction->getPayment()->getPaymentInstruction()->getExtendedData()->get('token');
    }
}
