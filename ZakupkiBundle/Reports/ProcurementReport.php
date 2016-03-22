<?php
/**
 * Created by PhpStorm.
 * User: ekretsu
 * Date: 18.11.15
 * Time: 17:13
 */

namespace ZakupkiBundle\Reports;

/**
 * Class OpenCompetitionReport
 * @package ZakupkiBundle\Reports
 */
class ProcurementReport extends ReportAbstract {

    const XML_TEMPLATE = 'ZakupkiBundle:Consumer/Plan:procurement_xml.html.twig';
    const XSD_TEMPLATE = 'ZakupkiBundle:Consumer/Plan:procurement_xsd.html.twig';

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
        $data['SCHEME_VERSION'] = "00001";

        /** генерируем XML */
        $xml = $this->xmlBuilder->generateXML(static::XML_TEMPLATE, static::XSD_TEMPLATE, $data);

        return $xml;

    }

}