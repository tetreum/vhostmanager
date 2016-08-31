<?php
class DataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider vhostProvider
     */
    public function testVhostConversion($providerName, $expectedResult)
    {
        $class = "\\VHostManager\\Providers\\" . $providerName;
        $provider = new $class();
        $item = $provider->parseString($expectedResult);

        if (!$item) {
            throw new Exception("Error Processing Request", 1);
        }

        //@ToDo refactor this
        if ($providerName == "Apache") {
            $item = $item[0];
        }

        $result = $provider->getConversion($item);

        $this->assertEquals(rtrim($expectedResult), rtrim($result), "Checking $providerName");
    }

    public function vhostProvider()
    {
        $providers = [
            "Apache",
            "Nginx",
        ];
        $expectedResults = [];

        foreach ($providers as $provider) {
            $result = file_get_contents(dirname(__FILE__) . "/data/" . strtolower($provider));
            $expectedResults[] = [$provider, $result];
        }

        return $expectedResults;
    }
}
?>
