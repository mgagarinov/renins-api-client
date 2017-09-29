<?php

namespace ReninsApi\Client;

/**
 * API client v2
 */
class ApiVersion2 extends BaseApi
{
    use Methods\V2\VehicleBrands;
    use Methods\V2\VehicleModels;
    use Methods\V2\VehicleAntitheftDevices;
    use Methods\V2\CreditBanks;
    use Methods\V2\CreditLeasing;
    use Methods\V2\PriceCalculated;
    use Methods\V2\StoaList;

    use Methods\V2\Calculation;
    use Methods\V2\Import;
    use Methods\V2\Printing;

    protected static $wsdlCalc = '';
    protected static $wsdlCalcTest = '';

    protected static $wsdlImport = '';
    protected static $wsdlImportTest = '';

    protected static $wsdlPrint = '';
    protected static $wsdlPrintTest = '';

    protected static $urlRest = '';
    protected static $urlRestTest = '';

}
