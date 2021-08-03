<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "vendor/autoload.php";
use \FeaturePhp as fphp;

class Renderer {
    private $page;

    private function getPages() {
        return json_decode(file_get_contents(__DIR__."/pages.json"), true);
    }

    private function getPage($slug) {
        foreach ($this->getPages() as $page)
                if ($page["slug"] === $slug)
                    return $page;
    }

    public function __construct($slug) {
        if (!$slug)
            $slug = "index";
        $this->page = $this->getPage($slug);
        if (!$this->page)
            $this->page = $this->getPage("index");
        header("Content-Type: text/html; charset=utf-8");
    }

    private function getProperty($prop, $page = null) {
        if (!$page)
            $page = $this->page;
        return array_key_exists($prop, $page) ? $page[$prop] : "";
    }

    public function render() {
        echo fphp\File\TemplateFile::render(
            "layout.html",
            array(
                array("assign" => "slug", "to" => $this->getProperty("slug")),
                array("assign" => "title", "to" => $this->getProperty("title")),
                array("assign" => "body", "to" => $this->getProperty("body")),
                array("assign" => "background", "to" => $this->getProperty("background")),
                array("assign" => "navigation", "to" => $this->getNavigation()),
                array("assign" => "overviewNavigation", "to" => $this->getOverviewNavigation()),
                array("assign" => "songs", "to" => $this->getSongs())
            ),
            __DIR__);
    }

    private function getNavigation() {
        $nav = "";
        foreach ($this->getPages() as $page) {
            $href = isset($page["slug"]) ? "?p=$page[slug]" :
                  (isset($page["href"]) ? $page["href"] : "javascript:void(0)");
            $active = isset($page["slug"]) && $this->getProperty("slug") === $page["slug"] ? "active" : "";
            $nav .= "<li class=\"$active\"><a href=\"$href\">$page[title]</a></li>\n";
        }
        return $nav;
    }

    private function getOverviewNavigation() {
        $nav = "<ul class=\"sheet-music overview\">";
        foreach ($this->getPages() as $page) {
            if (!$this->getProperty("summary", $page))
                continue;
            $href = isset($page["slug"]) ? "?p=$page[slug]" :
                  (isset($page["href"]) ? $page["href"] : "javascript:void(0)");
            $nav .= "<li><a href=\"$href\"><p><strong>$page[title]</strong></p><p><span>".$this->getProperty("summary", $page)."</span></p></a></li>\n";
        }
        return $nav."</ul>";
    }

    private function getSongs() {
        $songs = $this->getProperty("songs");
        if (!$songs)
            return "";
        $html = "<ul class=\"sheet-music\">";
        foreach ($songs as $song)
            $html .= "<li><p>".$this->getProperty("number", $song)." <strong>".$this->getProperty("title", $song)."</strong> ".$this->getProperty("subtitle", $song)."</p><ul><li>".($this->getProperty("listen", $song) ? "<a href=\"".$this->getProperty("listen", $song)."\" target=\"_blank\">Listen</a>" : "")."</li><li>".($this->getProperty("download", $song) ? "<a href=\"".$this->getProperty("download", $song)."\" target=\"_blank\">Download</a>" : "")."</li></ul></li>\n";
        return $html."</ul>";
    }
}

$page = isset($_GET["p"]) ? $_GET["p"] : null;
$renderer = new Renderer($page);
$renderer->render();