<?php
namespace ideasonpurpose\SVG;

/**
 * Collection of reusable, optimized SVG elements.
 *
 * TODO: Handle missing SVGs
 * TODO: Load SVGs from the filesystem and convert them to symbols
 */
class SVG
{
    // Test circle
    public static $test = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="100"/></svg>';
    public static $logo = '<svg width="56px" height="56px" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg"><circle id="Oval-6" cx="36" cy="36" r="36"></circle><path fill="#fff" d="M47.3793691,52.5326162 C45.2312252,52.5326162 43.4736529,50.7729008 43.4736529,48.5978487 C43.4736529,46.4227967 45.2312252,44.6630812 47.3793691,44.6630812 C47.7916392,44.6630812 48.1822108,44.7286607 48.551084,44.8488897 C48.6921237,44.9035392 48.8331635,44.9581888 48.9742032,45.0128383 C50.3303547,45.6358432 51.2742361,47.0020819 51.2742361,48.5978487 C51.2850853,50.7729008 49.527513,52.5326162 47.3793691,52.5326162 Z M32.2447189,43.6793893 C29.7927971,43.6793893 27.4710658,42.7066273 25.7026443,40.935982 C23.9776197,39.2090562 23.0120398,36.9247051 22.968643,34.4982651 L22.968643,34.1703678 C23.0554367,29.0988897 27.1781371,24.9892436 32.2447189,24.9892436 C37.3546976,24.9892436 41.5207948,29.1863289 41.5207948,34.3343164 C41.5207948,39.482304 37.3546976,43.6793893 32.2447189,43.6793893 Z M21.0157849,39.6353227 C21.0157849,40.8813324 20.5709672,41.9743234 19.7572763,42.7175573 C18.9761331,43.4280014 17.9129104,43.7558987 16.7628939,43.6356697 C14.6689961,43.4280014 13.2043525,41.7885149 13.2043525,39.6571825 L13.2043525,35.2524289 L13.2043525,33.3069049 C14.2458768,34.2468772 15.6128775,34.8261624 17.1209179,34.8261624 C18.6181091,34.8261624 19.9851098,34.2578071 21.0266341,33.3178348 L21.0266341,34.028279 L21.0266341,34.3343164 L21.0266341,34.6403539 L21.0157849,39.6353227 Z M17.1100687,24.9783137 C19.2690618,24.9783137 21.0157849,26.7489591 21.0157849,28.9130812 C21.0157849,31.0881332 19.2582126,32.8478487 17.1100687,32.8478487 C14.9510756,32.8478487 13.2043525,31.0772033 13.2043525,28.9130812 C13.2043525,26.7380291 14.9619248,24.9783137 17.1100687,24.9783137 Z M44.8298044,42.3350104 C44.8732012,42.37873 44.9165981,42.4224497 44.9599949,42.4661693 C44.9816933,42.4880291 45.0033918,42.509889 45.0250902,42.5208189 C45.0359394,42.5317488 45.0359394,42.5317488 45.0467886,42.5426787 C45.2095268,42.6956974 45.372265,42.8487162 45.5458524,43.0017349 C44.7755583,43.2640527 44.0703596,43.6793893 43.4845021,44.204025 L43.4845021,40.6955239 C43.8750738,41.2748092 44.3198914,41.8213046 44.8298044,42.3350104 Z M52.7497289,24.9892436 C57.8597075,24.9892436 62.0258048,29.1863289 62.0258048,34.3343164 C62.0258048,39.482304 57.8597075,43.6793893 52.7497289,43.6793893 C52.142173,43.6793893 51.5346172,43.6138099 50.9487597,43.5045108 C49.2020367,43.1328938 47.5963533,42.269431 46.294448,40.9578418 C45.4048126,40.0615892 44.7104631,39.0232477 44.243947,37.8865371 C44.1571533,37.6679389 44.0812088,37.4493407 44.0161135,37.2307425 L44.0052643,37.1979528 C43.9727167,37.1105135 43.940169,37.0230743 43.9184706,36.935635 C43.679788,36.1049618 43.5495974,35.230569 43.5495974,34.3452464 C43.5495974,29.9951423 46.5114322,26.3226926 50.5147913,25.2843511 C51.21999,25.0876128 51.9685856,24.9892436 52.7497289,24.9892436 Z M64.087155,34.3343164 C64.087155,28.082408 59.0422716,23 52.8365226,23 C51.936038,23 51.0681011,23.1092991 50.2218626,23.3060375 C46.7609641,24.1039209 43.9184706,26.519431 42.4863747,29.7218945 C40.7396516,25.7761971 36.8013878,23.0218598 32.2447189,23.0218598 C28.3607012,23.0218598 24.9323503,25.0220333 22.9143969,28.0496183 C22.4912777,25.2078418 20.0502051,23.0109299 17.1209179,23.0109299 C13.8878529,23.0109299 11.2623436,25.6559681 11.2623436,28.9130812 L11.2623436,28.9240111 C11.2514944,32.2467037 11.2514944,33.5036433 11.2623436,35.2633588 L11.2623436,39.6681124 C11.2623436,41.2092297 11.793955,42.6410479 12.7703841,43.723109 C13.7359639,44.7833102 15.0812661,45.4500347 16.5676081,45.6030534 C16.7845924,45.6249133 17.0124258,45.6358432 17.22941,45.6358432 C18.6940536,45.6358432 20.0393558,45.1330673 21.0808802,44.1821652 C22.0898568,43.2640527 22.7191111,42.018043 22.9143969,40.6080847 C23.326667,41.2201596 23.8040323,41.8103747 24.3356436,42.3459403 C26.4620891,44.4772727 29.2720349,45.657703 32.2555681,45.657703 C36.1070382,45.657703 39.5136907,43.6903192 41.5316441,40.7064539 L41.5316441,48.5978487 C41.5316441,51.8549618 44.1571533,54.5 47.3902183,54.5 C50.6232834,54.5 53.2487926,51.8549618 53.2487926,48.5978487 L53.2487926,45.657703 C59.2592558,45.4391048 64.087155,40.444136 64.087155,34.3343164 Z"></path></svg>';
    public static $arrow = '<svg width="25px" height="10px" viewBox="0 0 25 10" xmlns="http://www.w3.org/2000/svg"><symbol viewBox="0 0 25 10"><g stroke="none" stroke-width="1" fill-rule="evenodd"><path d="M0,5.56818182 L0.0132296399,4.40340909 C12.5986928,4.48193734 18.8549727,4.48193734 18.7820692,4.40340909 L18.7820692,0 L25,4.98579545 L18.7688396,10 L18.7820692,5.56818182 L0,5.56818182 Z"></path></g></symbol></svg>';

    private $lib = [];
    public $libDir = null;

    public function __construct($libDir = null)
    {
        $this->inUse = [];
        $this->libDir = $libDir ? $libDir : $this->libDir;
        $this->loadFromDirectory();
        $this->libFill();

        add_action('pre_get_posts', function () {
            set_query_var('SVG', $this);
        });

        add_action('wp_footer', [$this, 'dumpSymbols']);
    }

    /**
     * Checks the `$this->libDir` for SVG files and includes
     * any found using the files' baseName as the storage key.
     */
    public function loadFromDirectory()
    {
        if ($this->libDir && file_exists($this->libDir) && is_dir($this->libDir)) {
            $iterator = new \RecursiveDirectoryIterator($this->libDir);
            foreach (new \RecursiveIteratorIterator($iterator) as $file) {
                if (strtolower($file->getExtension()) === 'svg') {
                    $key = str_replace($this->libDir, '', $file->getPathname());
                    $key = preg_replace('/\.svg$/i', '', $key);
                    $key = strtolower(ltrim($key, '/'));

                    $this->lib[$key] = preg_replace(
                        ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                        ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                        file_get_contents($file->getRealPath()),
                    );
                }
            }
        }
    }

    /**
     * Alias for `debug`
     */
    public function directory()
    {
        $this->debug();
    }

    /**
     * prints a table of registered SVGs
     */
    public function debug()
    {
        $id = 'debug-' . md5(microtime(true));
        echo "<style>
            #$id {
                display: grid;
                grid-template-columns: repeat(2, 36px auto);
                align-items: center;
                justify-content: center;
                margin: 1rem auto;
                padding: 1rem;

                color: #c00;
                font-size: 14px;
                font-family: monospace;
                white-space: nowrap;
                column-gap: 6px;

                background: #eee;

                row-gap: 6px;
            }
            #$id svg {
                color: magenta;
                width: 36px;
                height: 36px;
                background: #ccc;
                fill: #555;
            }
            </style>";

        echo '<div id="' . $id . '" >';
        foreach ($this->lib as $key => $svg) {
            printf('<div>%s</div>', $this->get($key));
            echo "<div style='color: #bbb'>\$SVG->get(\"<span style='color:#c00'>$key</span>\")</div>";
        }
        echo '</div>';
    }

    /**
     * Copies static variables into $this->lib
     * This is largely for compatibility since we've previously been echoing static variables as needed
     * In the future, this can be refactored away and SVG content can be directly entered into $this->lib
     *
     * Note: `get_class_vars(get_called_class())` pulls static vars in from the child class
     */
    private function libFill()
    {
        $static_vars = get_class_vars(get_called_class());
        foreach ($static_vars as $key => $svg) {
            if (is_string($svg) && substr($svg, 0, 4) == '<svg') {
                $this->lib[$key] = preg_replace(
                    ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                    ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                    $svg,
                );
            }
        }
    }

    /**
     * Records a symbol as being used, then returns an SVG "use" reference to that symbol
     *
     * Writes an error message in an HTML comment if an SVG can not be found.
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->lib)) {
            error_log("SVG Lib Error: The key '$key' does not match any registered SVGs");
            return "\n<!-- SVG Lib Error: The key '$key' does not match any registered SVGs -->\n\n";
        }
        array_push($this->inUse, $key);
        return sprintf('<svg class="%1$s"><use xlink:href="#%1$s" href="#%1$s" /></svg>', $key);
    }

    /**
     * Prints an SVG containing all the symbols referenced in the document.
     */
    public function dumpSymbols()
    {
        if (count($this->inUse)) {
            $this->inUse = array_unique($this->inUse);
            sort($this->inUse);
            $symbols = array_map(function ($key) {
                return $this->lib[$key];
            }, $this->inUse);
            printf(
                "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n",
                implode("\n", $symbols),
            );
        } else {
            if (is_user_logged_in()) {
                printf("\n<!-- NO SVGs IN USE -->\n<!-- message from %s -->\n\n", __FILE__);
            }
        }
    }
}
