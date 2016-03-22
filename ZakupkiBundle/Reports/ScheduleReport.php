<?php
/**
 * Created by PhpStorm.
 * User: ekretsu
 * Date: 18.11.15
 * Time: 17:13
 */

namespace ZakupkiBundle\Reports;

/**
 * Class ScheduleReport
 * @package ZakupkiBundle\Reports
 */
class ScheduleReport extends ReportAbstract {

    const XML_TEMPLATE = 'ZakupkiBundle:Consumer/Plan:schedule_xml.html.twig';
    const XSD_TEMPLATE = 'ZakupkiBundle:Consumer/Plan:schedule_xsd.html.twig';

    /**
     * Получение XML
     * @return null
     * @throws \Exception
     */
    public function getXML(){

        $foifRawData = $this->message;
        $data = [];
        $data['foiv'] = $foifRawData;
        $data['plan'] = $foifRawData->rawPlan;
        $data['lots'] = $foifRawData->rawLots;
        $data['SCHEME_VERSION'] = "00001";

        /** генерируем XML */
        $xml = $this->xmlBuilder->generateXML(static::XML_TEMPLATE, static::XSD_TEMPLATE, $data);

        return $xml;
    }
}