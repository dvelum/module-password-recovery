<?php
abstract class Dvelum_Sitemap_Adapter
{
    const CHANGEFREQ_HOURLY = 'hourly';
    const CHANGEFREQ_DAILY = 'daily';
    const CHANGEFREQ_WEEKLY = 'weekly';
    const CHANGEFREQ_MONTHLY = 'monthly';
    const CHANGEFREQ_YEARLY= 'yearly';
    const CHANGEFREQ_NEVER = 'never';

    protected $host = '127.0.0.1';
    protected $scheme = 'http://';

    /**
     * Set sitemap http scheme
     * @param $scheme
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
    }

    /**
     * Set HTTP_HOST
     * @param $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Get item xml
     * @param $url
     * @param $lastMod
     * @param string $modTime
     * @param float $priority
     * @return string
     */
    protected function getItem($url , $lastMod , $modTime = self::CHANGEFREQ_WEEKLY, $priority = 0.8)
    {
        return '<url>'
                   .'<loc>' . $this->scheme . $this->host . $url . '</loc>'
                   .'<lastmod>' . $lastMod . '</lastmod>'
                   .'<changefreq>'.$modTime.'</changefreq>'
                   .'<priority>'.$priority.'</priority>'
               .'</url>';
    }

    public function __toXml()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml.= $this->getItems();
        $xml.= '</urlset>';
    }

    /**
     * Get Sitemap urlset items
     * @return string
     */
    abstract protected function getItems();
}