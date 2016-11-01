<?php

require('vendor/autoload.php');
require('translateclass.php');
use League\Csv\Reader;

$translater = new Translateclass();
$languagesArray = $translater->getLanguagesAvailable();

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

    $reader = Reader::createFromPath($filetmp);
    $reader = $reader->fetchAll();

    /**
     * Go through each language and generate the CSV language files
     */

    foreach($_POST['language'] as $language){
        $translatedTextArray = $translater->processTranslationByRow($reader, $language);
        $languageCode = $translater->getMageLanguageCode($language);
        $translater->generateCSV($translatedTextArray, $languageCode);
    }

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
    <?php if($translater->filewritesuccess != ''){
      ?><div class="alert alert-success" role="alert"><?php echo $translater->filewritesuccess; ?></div>
    <?php
    }
    ?>
    <form method="post" enctype="multipart/form-data">
        <label for="languages">Translate language file to: </label>
        <select multiple="multiple" name="language[]" size="15">
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
