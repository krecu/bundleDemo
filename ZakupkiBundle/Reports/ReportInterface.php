<?php
/**
 * Created by PhpStorm.
 * User: ekretsu
 * Date: 18.11.15
 * Time: 17:13
 */

namespace ZakupkiBundle\Reports;


interface ReportInterface {
    public function transducerData();
    public function getData($messageId, $url, array $query = null);
}