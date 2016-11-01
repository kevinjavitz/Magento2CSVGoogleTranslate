<?php

require('vendor/autoload.php');
use League\Csv\Reader;

/**
 * Load Google API Instance
 */
$client = new Google_Client();

$client->setApplicationName("Client_Library_Examples");
// Set your developer key here, make sure it is enabled for domain you are using this from
// and Google Translate API
// https://console.developers.google.com
$client->setDeveloperKey("");
$service = new Google_Service_Translate($client);

/**
 * Get list of languages we can translate to
 */
$sourceLanguage = 'en';
$filewritesuccess = '';
$langavailable = $service->languages;
$languages = $langavailable->listLanguages(['target' => $sourceLanguage]);
$languagesArray = $languages['data']['languages'];
$linesToProcess = 50; // how many lines of the csv to process in one translation request. Google limits to 2000 chars.

/**
 * Process form if submitted
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * Get and process file, validate is CSV
     */
    ini_set('auto_detect_line_endings', TRUE);
    $filetmp = $_FILES['magelangfile']['tmp_name'];
    $file_name = $_FILES['magelangfile']['name'];
    $file_namelower = strtolower($_FILES['magelangfile']['name']);

    preg_match('/\.[^\.]+$/i',$file_namelower,$ext);
    if($ext[0] != ".csv"){
        die("File extension not allowed, please choose a CSV file.");
    }

    /**
     * Example of Magento translate file: text to translate, translation
     * here we generate a big array of text to translate by going
     * line by line in the csv file
     */

    $handle = fopen($filetmp, 'r');
    $translateArray = [];
    $reader = Reader::createFromPath($filetmp);
    $reader = $reader->fetchAll();
    $curRow = 0;
    $totalRows = (int)count($reader);
    $textToTranslateArray = [];
    $originalCSVLanguageArray = [];
    $translatedTextArray = [];

    function processTranslations($service, $textToTranslateArray, $sourceLanguage){
        $translations = $service->translations;
        $destinationLanguage = $_POST['language'];
        $translated = $translations->listTranslations($textToTranslateArray, $destinationLanguage, ['source' => $sourceLanguage]);
        return $translated['data']['translations'];
    }

    for($curRow;$curRow<$totalRows;$curRow++) {
        $textToTranslateArray[] = $reader[$curRow][0];
        $originalCSVLanguageArray[] = $reader[$curRow][0];
        if(($curRow != 0 && (($curRow % $linesToProcess) == 0)) || // if we are in a multiple of lines to process
            (($curRow + 1) == $totalRows)){ //
            $translationsArray = processTranslations($service, $textToTranslateArray, $sourceLanguage);
            $translatedTextArray = array_merge($translatedTextArray, $translationsArray);
            $textToTranslateArray = []; // reset
        }
//        while (($data = fgetcsv($handle)) !== false) {
//            $textToTranslateArray[] = $data[0];
//        }
    }



    // debug translate Array
    // $translateArray = ['hello how are you', 'i have a dog'];



    /**
     * Re-Assemble CSV file from the translations
     */
    $len = count($translatedTextArray);
    $fileText = '';
    for ($i = 0; $i < $len; $i++) {
        // format is 'text to translate','translated text'
        $fileText .= '"' . $originalCSVLanguageArray[$i] . '","' . $translatedTextArray[$i]['translatedText'] . '"' . PHP_EOL;
    }
    $newfile = fopen($file_name, 'w') or die("Unable to open file!");
    if (fwrite($newfile, $fileText)){
        $filewritesuccess = "File created successfully at: " . getcwd() . DIRECTORY_SEPARATOR . $file_name;
    };
    fclose($newfile);
    //var_dump($translated);
}

//var_dump($translated);
?>
<html>
<body>
<link href="vendor/twbs/bootstrap/dist/css/bootstrap-theme.min.css" rel="stylesheet">
<link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container">
    <div class="page-header">
        <h1>Magento 2 Language File CSV Translator</h1>
    </div>
    <?php if($filewritesuccess != ''){
      ?><div class="alert alert-success" role="alert"><?php echo $filewritesuccess; ?></div>
    <?php
    }
    ?>
    <form method="post" enctype="multipart/form-data">
        <label for="languages">Translate language file to: </label>
        <select name="language">
            <?php
            foreach ($languagesArray as $language) {
                echo '<option value=' . $language['language'] . '>' . $language['name'] . '</option>';
            }
            ?>
        </select><br/>
        <label for="magelangfile">Magento language CSV file</label>
        <input type="file" name="magelangfile"><br/>
        <input type="submit">
    </form>
</div>
</body>
</html>
