<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

// Boot the application
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$exp = new App\Services\Iso23387Exporter();

try {
    $json = json_decode($exp->exportToJson(1), true);

    if ($json) {
        echo "✓ JSON Export Successful\n";
        echo "  Library Keys: " . implode(", ", array_keys($json['Library'])) . "\n";
        echo "  DataTemplates: " . count($json['Library']['DataTemplates'] ?? []) . "\n";
        echo "  GroupOfProperties: " . count($json['Library']['GroupOfProperties'] ?? []) . "\n";
        echo "  Properties: " . count($json['Library']['Properties'] ?? []) . "\n";
        echo "  ReferenceDocuments: " . count($json['Library']['ReferenceDocuments'] ?? []) . "\n";

        // Check first property has DataType
        if (!empty($json['Library']['Properties'])) {
            $firstProp = $json['Library']['Properties'][0];
            echo "  First Property has DataType: " . (isset($firstProp['DataType']) ? "YES (" . $firstProp['DataType']['name'] . ")" : "NO") . "\n";
        }
    } else {
        echo "✗ JSON Export failed: Invalid JSON\n";
    }
} catch (Exception $e) {
    echo "✗ JSON Export Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";

try {
    $xml = $exp->exportToXml(1);
    if ($xml && strpos($xml, '<?xml') === 0) {
        echo "✓ XML Export Successful\n";
        echo "  Length: " . strlen($xml) . " bytes\n";

        // Check for required elements
        echo "  Contains 'dt:Library': " . (strpos($xml, 'dt:Library') !== false ? "YES" : "NO") . "\n";
        echo "  Contains 'dt:DataType': " . (strpos($xml, 'dt:DataType') !== false ? "YES" : "NO") . "\n";
        echo "  Contains 'dt:Definition': " . (strpos($xml, 'dt:Definition') !== false ? "YES" : "NO") . "\n";
        echo "  Contains 'dt:Language': " . (strpos($xml, 'dt:Language') !== false ? "YES" : "NO") . "\n";

        // Save to file
        file_put_contents('storage/test_export.xml', $xml);
        echo "  Saved to: storage/test_export.xml\n";
    } else {
        echo "✗ XML Export failed: Invalid XML\n";
    }
} catch (Exception $e) {
    echo "✗ XML Export Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
