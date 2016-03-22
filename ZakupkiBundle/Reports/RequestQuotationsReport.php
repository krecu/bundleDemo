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
class RequestQuotationsReport extends ReportAbstract {

    const XML_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:request_quotations_xml.html.twig';
    const XSD_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:request_quotations_xsd.html.twig';

    public function getXML(){
        $lotsId = $this->objectId;

        $lot = $this->getData('isz-lot-' . microtime(), "/api/lots/$lotsId", ['x_groups' => 'notice_ea']);

        $data = [
            'lot' => $lot,
            'SCHEME_VERSION' => self::SCHEME_VERSION
        ];

        $xml = $this->xmlBuilder->generateXML(self::XML_TEMPLATE, self::XSD_TEMPLATE, $data);
        return $xml;
    }

    public function transducerData(){
        return "OpenCompetitionReport";
    }
}