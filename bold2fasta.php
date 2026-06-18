#!/usr/bin/env php
<?php
// Ensure CLI usage
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

// Check arguments
if ($argc < 3) {
    fwrite(STDERR, "Usage: php bold2fasta.php input.tsv output.fasta\n");
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

// Validate input file
if (!file_exists($inputFile)) {
    fwrite(STDERR, "Error: Input file does not exist.\n");
    exit(1);
}

// Open input
$handle = fopen($inputFile, "r");
if ($handle === false) {
    fwrite(STDERR, "Error: Could not open input file.\n");
    exit(1);
}

// Open output
$outHandle = fopen($outputFile, "w");
if ($outHandle === false) {
    fwrite(STDERR, "Error: Could not open output file.\n");
    fclose($handle);
    exit(1);
}

// Read header row
$header = fgetcsv($handle, 0, "\t");
if ($header === false) {
    fwrite(STDERR, "Error: Could not read header row.\n");
    fclose($handle);
    fclose($outHandle);
    exit(1);
}

// Map column names to indices
$columns = array();
foreach ($header as $index => $name) {
    $columns[trim($name)] = $index;
}

// Required fields
$required = array("nuc", "processid", "identification", "bin_uri", "country/ocean", "province/state");

// Check required columns exist
foreach ($required as $field) {
    if (!isset($columns[$field])) {
        fwrite(STDERR, "Error: Missing required column: $field\n");
        fclose($handle);
        fclose($outHandle);
        exit(1);
    }
}

// Process rows
$count = 0;
$written = 0;

while (($row = fgetcsv($handle, 0, "\t")) !== false) {
    $count++;

    $nuc = trim($row[$columns["nuc"]]);
    
    // Skip short sequences
    if (strlen($nuc) < 500) {
        continue;
    }

    $processid = trim($row[$columns["processid"]]);
    $identification = trim($row[$columns["identification"]]);
    $bin_uri = trim($row[$columns["bin_uri"]]);
    $country = trim($row[$columns["country/ocean"]]);
    $state = trim($row[$columns["province/state"]]);

    // Build FASTA header
    $headerLine = ">" . $identification . " (" . $processid . " " . $bin_uri . " " . $state . " " . $country . ")";

    // Write to file
    fwrite($outHandle, $headerLine . "\n");
    fwrite($outHandle, $nuc . "\n");

    $written++;
}

// Cleanup
fclose($handle);
fclose($outHandle);

// Report
fwrite(STDOUT, "Processed $count rows.\n");
fwrite(STDOUT, "Wrote $written sequences to $outputFile.\n");
