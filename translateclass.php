<?php
require('vendor/autoload.php');
use League\Csv\Reader;

class Translateclass {

    /**
     * language code to Magento language code array
     * this list is not complete, just the most common ones we translate for
     * Mage 2 locales list is at Magento\Framework\Locale\Config.php
     */

    public $languageToMageLanguage = [
        'af'    =>  'af_ZA',
        'ar'    =>  'ar_SA',
        'be'    =>  'be_BY',
        'da'    =>  'da_DK',
        'de'    =>  'de_DE',
        'el'    =>  'el_GR',
        'es'    =>  'es_ES',
        'et'    =>  'et_EE',
        'fr'    =>  'fr_FR',
        'hr'    =>  'hr_HR',
        'id'    =>  'id_ID',
        'it'    =>  'it_IT',
        'iw'    =>  'he_IL',
        'ja'    =>  'ja_JP',
        'ko'    =>  'ko_KR',
        'nl'    =>  'nl_NL',
        'pt'    =>  'pt_BR',
        'ro'    =>  'ro_RO',
        'ru'    =>  'ru_RU',
        'th'    =>  'th_TH',
        'tl'    =>  'fil_PH',
        'sv'    =>  'sv_SE',
        'uk'    =>  'uk_UA',
        'vi'    =>  'vi_VN',
        'zh'    =>  'zh_Hans_CN'
    ];

    protected $i18ndir = 'i18n';

    /**
     * How many lines of the csv to process in one translation request. Google limits to 2000 chars.
     *
     * @var int
     */
    protected $linesToProcess = 50;

    protected $sourcelanguage = 'en';

    public $filewritesuccess = '';

    protected $originalCSVLanguageArray = [];


    /**
     * Load Google API Instance
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName("Client_Library_Examples");
        // Set your developer key here, make sure it is enabled for domain you are using this from
        // and Google Translate API
        // https://console.developers.google.com
        $this->client->setDeveloperKey("");
        $this->service = new Google_Service_Translate($this->client);
    }

    /**
     * Gets languages available and returns them as an array
     *
     * @return mixed
     */
    public function getLanguagesAvailable(){
        $langavailable = $this->service->languages;
        $languages = $langavailable->listLanguages(['target' => $this->sourcelanguage]);
        return $languages['data']['languages'];
    }

    public function processTranslationByRow($reader, $destinationLanguage){
        $totalRows = (int)count($reader);
        $translatedTextArray = [];
        for($curRow=0;$curRow<$totalRows;$curRow++) {
            $textToTranslateArray[] = $reader[$curRow][0];
            /**
             * original CSV language array used later for rebuilding first column of CSV
             */
            if(count($this->originalCSVLanguageArray)<= $totalRows) {
                $this->originalCSVLanguageArray[] = $reader[$curRow][0];
            }
            if(($curRow != 0 && (($curRow % $this->linesToProcess) == 0)) || // if we are in a multiple of lines to process
                (($curRow + 1) == $totalRows)){ //
                $translationsArray = $this->processTranslations($textToTranslateArray, $destinationLanguage);
                $translatedTextArray = array_merge($translatedTextArray, $translationsArray);
                $textToTranslateArray = []; // reset
            }
        }
        return $translatedTextArray;
    }

    /**
     * Takes a source array and translates to the destination language
     *
     * @param $textToTranslateArray
     * @param $sourceLanguage
     * @return mixed
     */
    public function processTranslations($textToTranslateArray, $destinationLanguage){
        $translations = $this->service->translations;
        $translated = $translations->listTranslations($textToTranslateArray, $destinationLanguage, ['source' => $this->sourcelanguage]);
        return $translated['data']['translations'];
    }

    /**
     * Searches languages array and returns the Mage language code
     *
     * @param $language
     * @param $languageToMageLanguage
     * @return mixed
     */
    public function getMageLanguageCode($language){
        if(array_key_exists($language, $this->languageToMageLanguage)){
            return $this->languageToMageLanguage[$language];
        } else {
            return $language;
        }
    }

    /**
     * Re-Assemble CSV file from the translations
     */
    public function generateCSV($translatedTextArray, $languageCode)
    {
        $len = count($translatedTextArray);
        $fileText = '';
        $file_name = $languageCode . '.csv';
        for ($i = 0; $i < $len; $i++) {
            // format is 'text to translate','translated text'
            $fileText .= '"' . $this->originalCSVLanguageArray[$i] . '","' . $translatedTextArray[$i]['translatedText'] . '"' . PHP_EOL;
        }
        $newfile = fopen($this->i18ndir . DIRECTORY_SEPARATOR . $file_name, 'w') or die("Unable to open file!");
        if (fwrite($newfile, $fileText)) {
            $this->filewritesuccess .= "File created successfully at: " . getcwd() . DIRECTORY_SEPARATOR . $this->i18ndir . DIRECTORY_SEPARATOR . $file_name . "<br />";
        };
        fclose($newfile);
    }

}