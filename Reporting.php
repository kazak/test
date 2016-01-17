<?php
/**
 * Created by PhpStorm.
 * User: dss
 * Date: 17.01.16
 * Time: 17:29
 */

/**
 * Class Reporting
 */
class Reporting
{
    /**
     * @var array
     */
    protected $_report = [];

    /**
     * @var string
     */
    protected $_reportName;

    /**
     * @var array
     */
    protected $_params = [
        'href' => 'URL',
        'processTime' => 'time',
        'imgLength' => 'count of img tegs',
    ];

    /**
     * @return string
     * @param array $report
     */
    public function setReport(array $report)
    {
        $this->_report = $report;
    }

    /**
     * @return string
     */
    public function getReportName()
    {
        return $this->_reportName;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function save()
    {
        $this->generateReportName();

        $file = $this->_reportName;
        $response = $this->generateResponse();

        if (!file_put_contents($file, $response)) {
            throw new Exception('Unable to write to a file: "' . $file . '"');
        }

        return true;
    }

    /**
     * file name
     */
    protected function generateReportName()
    {
        $this->_reportName = 'report_' . date('d.m.Y').".html";
    }

    /**
     * sort array
     *
     * @param array $array
     * @param array $orderArray
     * @return array
     */
    protected function sortArrayByArray(array $array, array $orderArray)
    {
        $ordered = [];

        foreach ($orderArray as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];

                unset($array[$key]);
            }
        }

        return $ordered + $array;
    }

    /**
     * @return string
     */
    public function generateResponse()
    {
        $response  = "<html><head></head><body> \r\n";
        $arrayResp = $this->arrayOrderBy($this->_report);

        foreach($arrayResp as $reportStrins){
            $response .= "url - " . $reportStrins["href"].
                ", count img tag - ". $reportStrins["imgLength"].
                ", time to run - ". $reportStrins["processTime"].
            "\r\n";
        }

        $response  .= "</body></html>";

        return $response;
    }

    /**
     * sort array
     *
     * @return mixed
     */
    protected function arrayOrderBy($data)
    {
        usort($data, function ($item1, $item2) {
            if ($item1['imgLength'] == $item2['imgLength']) return 0;

            return $item1['imgLength'] < $item2['imgLength'] ? 1 : -1;
        });

        return $data;
    }
}