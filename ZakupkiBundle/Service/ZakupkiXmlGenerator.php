<?php
/**
 * Created by PhpStorm.
 * User: dmitrii
 * Date: 16/10/15
 * Time: 13:26
 */

namespace ZakupkiBundle\Service;


/**
 * Class ZakupkiXmlGenerator
 * @package ZakupkiBundle\Service
 */
/**
 * Class ZakupkiXmlGenerator
 * @package ZakupkiBundle\Service
 */
class ZakupkiXmlGenerator
{
    /**
     * @var string
     */
    private $schemeVersion = '4.6';

    /**
     * @param string $body
     * @return string
     */
    public function generateCommonInfoXML($body)
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>';

        if (count($body['plan']) > 0  // Лот не включенный в план не должен никуда передаваться
            && (count($body['financings']) > 0 && isset($body['financings'][0]['year'])) // у финансирования должен быть год
        ) {
            $content .= '<sketchPlan>';
            $content .= '<SCHEME_VERSION>'.$this->schemeVersion.'</SCHEME_VERSION>>';
            $content .= '<commonInfo>';
            $content .=     '<id></id>';
            $content .=     '<externalId>'.$body['id'].'</externalId>';
            $content .=     '<planNumber>'.$body['number'].'</planNumber>';
            $content .=     '<year>'.$body['plan'][0]['year'].'</year>';
            $content .=     '<periodYearForm>'.$body['financings'][0]['year'].'</periodYearForm>';
            $content .=     '<periodYearTo>'.$body['financings'][count($body['financings']) - 1]['year'].'</periodYearTo>';
            $content .=     '<name>'.$body['title'].'</name>';
            $content .=     '<versionNumber>1</versionNumber>';
            $content .=     '<owner>';
            $content .=         '<regNum></regNum>';
            $content .=         '<fullName>'.$body['common']['full_name'].'</fullName>';
            $content .=     '</owner>';
            $content .=     '<createDate>'.$body['creation_date'].'</createDate>';
            $content .=     '<confirmDate>'.$body['creation_date'].'</confirmDate>';
            $content .=     '<publishDate>'.$body['creation_date'].'</publishDate>';
            $content .= '</commonInfo>';

            $content .= '<customerInfo>';
            $content .=     '<regNum></regNum>';
            $content .=     '<fullName>'.$body['common']['full_name'].'</fullName>';
            $content .=     '<factAddress>'.$body['common']['fact_address'].'</factAddress>';
            $content .=     '<INN>'.$body['common']['inn'].'</INN>';
            $content .=     '<KPP>'.$body['common']['kpp'].'</KPP>';
            $content .=     '<contactEMail>'.$body['common']['email'].'</contactEMail>';
            $content .= '</customerInfo>';

            $content .= '<attachments>';
            $content .=     '<attachment>';
            $content .=         '<publishedContentId></publishedContentId>';
            $content .=         '<fileName></fileName>';
            $content .=         '<fileSize></fileSize>';
            $content .=         '<docDescription></docDescription>';
            //echo         '<url></url>';
            // или
            //echo         '<contentId></contentId>';
            // или
            //echo         '<content></content>';
            $content .=         '<cryptoSigns>';
            $content .=             '<signature>';
            $content .=                 '<type></type>';
            $content .=             '</signature>';
            $content .=         '</cryptoSigns>';
            $content .=     '</attachment>';
            $content .= '</attachments>';

            $content .= '<printForm>';
            $content .=     '<url></url>';
            $content .=     '<signature>';
            $content .=         '<type></type>';
            $content .=     '</signature>';
            $content .= '</printForm>';

            $content .= '</sketchPlan>';

            return $content;
        } else {
            return $content.'<error>Лот нельзя передавать</error>';
        }
    }

    /**
     * @param string $body
     * @param object $foiv
     * @param object $plan
     * @param array  $lots
     * @return string
     */
    public function generatePlanXML($body, $foiv, $plan, $lots)
    {
        $content = '<?xml version="1.0" encoding="UTF-8" ?>';
        $content .= '<tenderPlan SCHEME_VERSION="'.$this->schemeVersion.'">';
        $content .= '<commonInfo>';
        $content .=     '<externalId>'.$plan->id.'</externalId>';
        $content .=     '<year>'.$plan->year.'</year>';
        $content .= '</commonInfo>';
        $content .= '<customerInfo>';
        $content .=     '<fullName>'.$foiv->fullName.'</fullName>';
        $content .=     '<postAddress>'.$foiv->postAddress.'</postAddress>';
        $content .=     '<factAddress>'.$foiv->factAddress.'</factAddress>';
        $content .=     '<INN>'.$foiv->inn.'</INN>';
        $content .=     '<KPP>'.$foiv->kpp.'</KPP>';
        $content .=     '<oktmo><code>'.$foiv->oktmo.'</code></oktmo>';
        $content .= '</customerInfo>';
        $content .= '<responsibleContactInfo>';
        $content .=     '<lastName>'.$foiv->head->lastName.'</lastName>';
        $content .=     '<firstName>'.$foiv->head->name.'</firstName>';
        $content .=     '<phone>'.$foiv->head->phone.'</phone>';
        $content .=     '<email>'.$foiv->head->email.'</email>';
        $content .= '</responsibleContactInfo>';
        $content .= '<providedPurchases>';
        $content .=     '<positions>';
        foreach ($lots as $lot) {
            $content .=     '<position>';
            $content .=         '<commonInfo>';
            if (!empty($lot->okved)) {
                $content .=        '<OKVEDs>';
                $content .=            '<code>'.$lot->okved->code.'</code>';
                $content .=            '<name>'.$lot->okved->description.'</name>';
                $content .=        '</OKVEDs>';
            }
            $content .=             '<сontractSubjectName></сontractSubjectName>';
            $content .=             '<contractMaxPrice></contractMaxPrice>';
            $content .=             '<payments></payments>';
            $content .=             '<contractCurrency>';
            $content .=                 '<code>RUB</code>';
            $content .=                 '<name>Российский рубль</name>';
            $content .=             '</contractCurrency>';
            $content .=             '<placingWay>';
            $content .=                 '<code></code>';
            $content .=                 '<name></name>';
            $content .=             '</placingWay>';
            $content .=             '<positionModification>';
            $content .=                 '<changeReason>';
            $content .=                     '<id></id>';
            $content .=                     '<name></name>';
            $content .=                 '</changeReason>';
            $content .=             '</positionModification>';
            $content .=             '<publicDiscussion></publicDiscussion>';
            $content .=             '<amountKBKs>';
            $content .=                 '<code>'.implode(
                    '.',
                    array_filter(
                        [
                            $lot->kbk->section,
                            $lot->kbk->subsection,
                            $lot->kbk->targetItem,
                            $lot->kbk->codeVr,
                            $lot->kbk->codeKosgy
                        ],
                        function ($item) {
                            return !empty($item);
                        }
                    )
                ).'</code>';
            $content .=                 '<yearsList>';
            $content .=                     '<year></year>';
            $content .=                     '<yearAmount></yearAmount>';
            $content .=                 '</yearsList>';
            $content .=             '</amountKBKs>';
            $content .=             '<amountKOSGUs>';
            $content .=                 '<code></code>';
            $content .=                 '<amount></amount>';
            $content .=             '</amountKOSGUs>';
            $content .=         '</commonInfo>';
            $content .=         '<producs>';
            foreach ($lot->stages as $stage) {
                foreach ($stage->workTypes as $workType) {
                    $content .=     '<product>';
                    $content .=         '<OKPD>';
                    $content .=             '<code>'.$lot->okpd->codeOkpd.'</code>';
                    $content .=             '<name>'.$lot->okpd->description.'</name>';
                    $content .=         '</OKPD>';
                    $content .=         '<name>'.$workType->title.'</name>';
                    $content .=         '<minRequirement>'.$lot->minRequirements.'</minRequirement>';
                    if (!empty($workType->okei)) {
                        $content .=     '<OKEI>';
                        $content .=         '<code>'.$workType->okei->code.'</code>';
                        $content .=         '<name>'.$workType->okei->description.'</name>';
                        $content .=     '</OKEI>';
                    }
                    $content .=         '<sumMax>'.$workType->pricePerUnit.'</sumMax>';
                    $content .=     '</product>';
                }
            }

            $content .=         '</producs>';
            $content .=         '<purchaseConditions>';
            $content .=             '<purchaseFinCondition>';
            $content .=                 '<amount>'.$lot->prepaymentBidPercentage.'</amount>';
            $content .=             '</purchaseFinCondition>';
            $content .=             '<purchaseGraph>';
            $content .=                 '<purchasePlacingTerm>';  // lot.order_publication
            $orderPublication = new \DateTime($lot->orderPublication);
            $content .=                     '<month>'.$orderPublication->format('m').'</month>';
            $content .=                     '<year>'.$orderPublication->format('Y').'</year>';
            $content .=                 '</purchasePlacingTerm>';
            $content .=                 '<contractExecutionTerm>';  // lot.contract_execution
            $contractExecution = new \DateTime($lot->contractExecution);
            $content .=                     '<month>'.$contractExecution->format('m').'</month>';
            $content .=                     '<year>'.$contractExecution->format('Y').'</year>';
            $content .=                 '</contractExecutionTerm>';
            $content .=             '</purchaseGraph>';
            $content .=             '<preferensesRequirement>';
            $content .=                 '<preferenses>';
            $content .=                     '<preferense>';
            $content .=                         '<code>'.$lot->preferences282944.'</code>';  // Херь какая-то, почему-то в базе bool стоит
            //$content.=                         '<name></name>';
            //$content.=                         '<prefValue></prefValue>';
            $content .=                     '</preferense>';
            $content .=                 '</preferenses>';
            $content .=                 '<requirements>';  // gov_requirements, rid_requirements, requirements_19_44, requirement_30_44
            $content .=                     '<requirement>';
            $content .=                         '<content>'.$lot->requirements1944.'</content>';  // Херь какая-то, почему-то в базе bool стоит пости у всего списка полей
            $content .=                     '</requirement>';
            $content .=                 '</requirements>';
            $content .=             '</preferensesRequirement>';
            $content .=         '</purchaseConditions>';
            $content .=     '</position>';
        }
        $content .=     '</positions>';
        $content .= '</providedPurchases>';
        $content .= '</tenderPlan>';

        return $content;
    }
}
