<?php declare(strict_types=1);

namespace Tests\Cases;

use DateTime;
use FilipSedivy\EET;
use Ramsey\Uuid\Uuid;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class DispatcherTest extends TestCase
{
    private EET\Certificate $certificate;

    public function testService(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, 'Personal/MySchema/MyService.wsdl');

        Assert::contains('Personal/MySchema/MyService.wsdl', $dispatcher->getService());

        $schemaPath = 'Schema';

        $dispatcher->setProductionService();

        Assert::contains($schemaPath . '/ProductionService.wsdl', $dispatcher->getService());

        $dispatcher->setPlaygroundService();

        Assert::contains($schemaPath . '/PlaygroundService.wsdl', $dispatcher->getService());

        $dispatcher->setService('Personal/MySchema/MyService.wsdl');

        Assert::contains('Personal/MySchema/MyService.wsdl', $dispatcher->getService());

        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PRODUCTION_SERVICE);

        Assert::contains($schemaPath . '/ProductionService.wsdl', $dispatcher->getService());
    }

    public function testSendReceipt(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        $dispatcher->send($this->getValidReceipt());

        Assert::type('string', $dispatcher->getFik());
        Assert::type('string', $dispatcher->getBkp());
        Assert::type('string', $dispatcher->getSoapClient()->lastResponse);
        Assert::same(200, $dispatcher->getSoapClient()->getLastResponseHttpCode());
        Assert::type(DateTime::class, $dispatcher->getSentDateTime());
    }

    public function testFailed(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);
        $dispatcher->setCurlOption(CURLOPT_PROXY, '127.0.0.1:8888');

        Assert::exception(function () use ($dispatcher): void {
            $dispatcher->send($this->getValidReceipt());
        }, EET\Exceptions\EET\ClientException::class);

        try {
            $dispatcher->send($this->getValidReceipt());
        } catch (EET\Exceptions\EET\ClientException $client) {
            Assert::type('string', $client->getBkp());
            Assert::type('string', $client->getPkp());
            Assert::null($dispatcher->getSoapClient()->getLastResponseHttpCode());
            Assert::type(DateTime::class, $dispatcher->getSentDateTime());

            if (!$client->getReceipt() instanceof EET\Receipt) {
                Assert::fail('Client->getReceipt() is not instanceof Receipt');
            }
        }
    }

    public function testCheck(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        $receipt = $this->getValidReceipt();
        Assert::true($dispatcher->check($receipt));

        $receipt->dic_popl = 'CZ00000018';
        Assert::false($dispatcher->check($receipt));
    }

    public function testGetCheckCodes(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        $receipt = $this->getValidReceipt();

        Assert::type('array', $dispatcher->getCheckCodes($receipt));

        $receipt->bkp = $dispatcher->getBkp();
        $receipt->pkp = $dispatcher->getPkp(false);

        Assert::type('array', $dispatcher->getCheckCodes($receipt));
    }

    public function testLastReceipt(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        Assert::null($dispatcher->getLastReceipt());

        $dispatcher->send($this->getValidReceipt());

        Assert::type(EET\Receipt::class, $dispatcher->getLastReceipt());
    }

    public function testGetWarnings(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        Assert::type('array', $dispatcher->getWarnings());
        Assert::count(0, $dispatcher->getWarnings());
    }

    public function testGetPkp(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        Assert::null($dispatcher->getPkp());

        $dispatcher->send($this->getValidReceipt());

        Assert::type('string', $dispatcher->getPkp());
    }

    public function testGetSentDateTime(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        Assert::null($dispatcher->getSentDateTime());

        $dispatcher->send($this->getValidReceipt());

        Assert::type(DateTime::class, $dispatcher->getSentDateTime());
    }

    public function testGetSoapClient(): void
    {
        $dispatcher = new EET\Dispatcher($this->certificate, EET\Dispatcher::PLAYGROUND_SERVICE);

        Assert::type(EET\SoapClient::class, $dispatcher->getSoapClient());
    }

    protected function setUp(): void
    {
        $this->certificate = EET\Certificate::fromFile(DATA_DIR . '/EET_CA1_Playground-CZ00000019.p12', 'eet');
    }

    private function getValidReceipt(): EET\Receipt
    {
        $receipt = new EET\Receipt;
        $receipt->uuid_zpravy = Uuid::uuid4()->toString();
        $receipt->id_provoz = '11';
        $receipt->id_pokl = 'IP105';
        $receipt->dic_popl = 'CZ00000019';
        $receipt->porad_cis = '1';
        $receipt->dat_trzby = new DateTime;
        $receipt->celk_trzba = 500;

        return $receipt;
    }
}

(new DispatcherTest)->run();
