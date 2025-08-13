<?php

/**
 * ReportConsommations class allows to generate reports about self consumptions
 */
class ReportConsommations extends Report
{
    /**
     * total amount of self consumption
     *
     * @var float
     */
    private float $totalC;
    
    /**
     * Class constructor
     *
     * @param string $plateforme reports for this given plateform
     * @param string $to last month of the period
     * @param string $from first month of the period
     */
    function __construct(string $plateforme, string $to, string $from)
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalC = 0.0;
        $this->tabs = [
            "consos" => [
                "title" => "Consommations propre",
                "columns" => ["item-nbr", "item-name"],
                "dimensions" => array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM),
                "operations" => ["conso-propre-march-expl", "conso-propre-extra-expl", "conso-propre-march-proj", "conso-propre-extra-proj"],
                "formats" => ["fin", "fin", "fin", "fin"],
                "results" => []
            ]
        ];
    }

    /**
     * prepares dimensions, and extracts and maps data
     *
     * @return void
     */
    function prepare(): void 
    {
        $this->preparePrestations();

        $columns = $this->bilansStats[$this->factel]['Bilan-c']['columns'];
        $lines = Csv::extract($this->getFileNameInBS('Bilan-c'));

        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            $itemId = $tab[$columns['item-id']];
            $prestation = $this->prestations[$itemId];

            if(!array_key_exists($itemId, $this->tabs["consos"]["results"])) {
                $this->tabs["consos"]["results"][$itemId] = [];
                foreach($this->tabs["consos"]["dimensions"] as $dimension) {
                    $this->tabs["consos"]["results"][$itemId][$dimension] = $prestation[$dimension];
                }
                foreach($this->tabs["consos"]["operations"] as $operation) {
                    $this->tabs["consos"]["results"][$itemId][$operation] = 0;
                }
            }
            foreach($this->tabs["consos"]["operations"] as $operation) {
                $this->tabs["consos"]["results"][$itemId][$operation] += $tab[$columns[$operation]];
                $this->totalC += $tab[$columns[$operation]];
            }
        }
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display():void
    {
        $title = '<div class="total">Total des consommations propres sur la période '.$this->period().' : '.$this->format($this->totalC).' CHF</div>';
        echo $this->templateDisplay($title);
    }

}
