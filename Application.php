<?php
/**
 * Created by PhpStorm.
 * User: dss
 * Date: 16.01.16
 * Time: 1:03
 */

/**
 * Class Application
 */
class Application
{
    /**
     * reporting
     *
     * @var array
     */
    public $report = [];

    /**
     * run crawler
     *
     * @return bool
     */
    public function run($url)
    {
        try {

            $this->crawl($url);
            $this->report($this->report);

        } catch (Exception $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    /**
     * Parsing
     *
     * @throws Exception
     */
    public function crawl($url)
    {
        $crawler = new Crawler();

        $crawler->on($crawler::EVENT_HIT_CRAWL, function ($href, DOMDocument $dom) {
            $start       = microtime(true);
            $imgLength   = $dom->getElementsByTagName('img')->length;
            $time        = microtime(true) - $start;
            $processTime = sprintf('%.6F', $time);

            $this->report[] = [
                'href' => $href,
                'imgLength' => $imgLength,
                'processTime' => $processTime
            ];
            $this->show('  - ' . $href . ' [img: ' . $imgLength . ']' . PHP_EOL);
        });

        $crawler->on($crawler::EVENT_BEFORE_CRAWL, function () {
            $this->show('Start crawl' . PHP_EOL);
        });

        $crawler->on($crawler::EVENT_AFTER_CRAWL, function () {
            $this->show('Finish crawl' . PHP_EOL);
        });

        $crawler->crawl($url);
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function report($data)
    {
        $reporting = new Reporting();

        if (!is_array($data)) {

            throw new Exception('Bad Request');
        }

        $reporting->setReport($data);
        $reporting->save();

        $this->show('Generate report file: ' . $reporting->getReportName() . PHP_EOL);
    }

    /**
     * show message
     *
     * @param $message
     */
    public function show($message)
    {
        echo $message;
    }

}