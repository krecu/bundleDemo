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
class ElectronicAuctionReport extends ReportAbstract {

    const XML_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:electronic_auction_xml.html.twig';
    const XSD_TEMPLATE = 'ZakupkiBundle:Consumer/Notice:electronic_auction_xsd.html.twig';

    public function getXML(){

        $lotsId = $this->message->objectId;

        $lot = $this->getData('isz-lot-' . microtime(), "/api/lots/$lotsId", ['x_groups' => 'notice_ea']);
        $data = [
            'lot' => $lot,
            'SCHEME_VERSION' => self::SCHEME_VERSION
        ];


        $xml = $this->xmlBuilder->generateXML(static::XML_TEMPLATE, static::XSD_TEMPLATE, $data);

        return $xml;
    }

    public function transducerData(){
        return "OpenCompetitionReport";
    }
}