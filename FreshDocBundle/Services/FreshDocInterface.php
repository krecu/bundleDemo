<?php
namespace FreshDocBundle\Services;

/**
 * Class FreshDocService
 * @package FreshDocBundle\Services
 */
interface FreshDocInterface
{
    public function generateDocumentTask($message);
    public function generateDocument($message);
    public function downloadDocument($message);
}