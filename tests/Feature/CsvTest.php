<?php

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\ExportModel;
use putyourlightson\campaign\models\ImportModel;

test('CSV files can be imported using a custom column delimiter', function() {
    $filePath = tempnam(sys_get_temp_dir(), 'campaign-csv-');

    try {
        file_put_contents($filePath, "email;firstName\ntest@example.com;Test\n");
        $import = new ImportModel([
            'fileName' => 'contacts.csv',
            'filePath' => $filePath,
            'delimiter' => 'semicolon',
        ]);

        expect(Campaign::$plugin->imports->getColumns($import))
            ->toBe(['email', 'firstName'])
            ->and(Campaign::$plugin->imports->getRows($import))
            ->toBe([['test@example.com', 'Test']]);
    } finally {
        @unlink($filePath);
    }
});

test('CSV files can be exported using a custom column delimiter', function() {
    $filePath = tempnam(sys_get_temp_dir(), 'campaign-csv-');
    $mailingList = createMailingList();
    $contact = createContact();

    try {
        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed');
        $export = new ExportModel([
            'filePath' => $filePath,
            'mailingListIds' => [$mailingList->id],
            'fields' => ['email' => true],
            'delimiter' => 'semicolon',
        ]);

        Campaign::$plugin->exports->exportFile($export);

        $handle = fopen($filePath, 'rb');
        $headers = fgetcsv($handle, separator: ';', escape: '\\');
        $row = fgetcsv($handle, separator: ';', escape: '\\');
        expect($headers)
            ->toBe(['mailingList', 'subscriptionStatus', 'dateSubscribed', 'email'])
            ->and([$row[0], $row[1], $row[3]])
            ->toBe([$mailingList->title, 'subscribed', $contact->email]);
        fclose($handle);
    } finally {
        @unlink($filePath);
    }
});
