<?php

require_once("Csv.php");

/**
 * ParamText class represents a csv file with all the key/label for the columns names
 */
class ParamText extends Csv
{

    /**
     * The csv files names
     */
    const NAME = "paramtext.csv";

    /**
     * The complete keys list
     */
    const LABELS = ["editing-year", "editing-month", "invoice-year", "invoice-month", "invoice-version", "invoice-project", "invoice-id", "invoice-type", "invoice-ref",
                    "platf-code", "platf-op", "platf-name", "client-code", "client-sap", "client-name", "client-name2", "client-name3", "client-ref", "client-email",
                    "client-deliv", "client-idclass", "client-class", "client-labelclass", "oper-id", "oper-sciper", "oper-name", "oper-first", "oper-note", "oper-PO",
                    "staff-note", "mach-id", "mach-name", "mach-extra", "user-id", "user-sciper", "user-name", "user-first", "user-name-f", "user-email", "proj-id",
                    "proj-nbr", "proj-nbr-0", "proj-name", "proj-name-0", "proj-expl", "flow-type", "flow-cae", "flow-noshow", "flow-lvr", "flow-srv", "item-grp",
                    "item-cae", "item-id", "item-idclass", "item-idsap", "item-codeK", "item-textK", "item-text2K", "item-K1", "item-K1a", "item-K1b", "item-K2", "item-K2a",
                    "item-K3", "item-K3a", "item-K4", "item-K4a", "item-K5", "item-K5a", "item-K6", "item-K6a", "item-K7", "item-K7a", "item-nbr", "item-name", "item-unit", "item-nbdeci",
                    "item-codeD", "item-flag-usage", "item-flag-conso", "item-eligible", "item-order", "item-labelcode", "item-extra", "transac-date", "transac-raw",
                    "transac-quantity", "transac-valid", "transac-id-staff", "transac-staff", "transac-usage", "transac-runtime", "transac-runcae", "valuation-price",
                    "valuation-brut", "discount-type", "discount-HC", "discount-CHF", "deduct-CHF", "sum-deduct", "valuation-net", "valuation-net-cancel", "valuation-net-notbill",
                    "subsid-code", "subsid-name", "subsid-type", "subsid-start", "subsid-end", "subsid-ok", "subsid-pourcent", "subsid-maxproj", "subsid-maxmois", "subsid-reste",
                    "subsid-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus", "total-fact", "runtime-N", "runtime-avg", "runtime-stddev", "conso-propre-march-expl",
                    "conso-propre-extra-expl", "conso-propre-march-proj", "conso-propre-extra-proj", "year", "month", "day", "week-nbr", "subsid-alrdygrant", "your-ref",
                    "stat-nbuser-d", "stat-nbuser-w", "stat-nbuser-m", "stat-nbuser-3m", "stat-nbuser-6m", "stat-nbuser-12m", "stat-trans", "stat-run", "stat-hmach", "stat-hoper",
                    "stat-run-user", "stat-nbuser", "stat-nbclient", "stat-nbclient-m", "stat-nbclient-3m", "stat-nbclient-6m", "stat-nbclient-12m", "date-start-y", "date-start-m",
                    "date-end-y", "date-end-m", "version-last", "version-change", "version-old-amount", "version-new-amount", "annex-client-titre1", "annex-client-titre2",
                    "annex-client-abrev-platform", "annex-client-proj-no", "annex-client-name-platform", "annex-client-user", "annex-client-start", "annex-client-end",
                    "annex-client-prestation", "annex-client-quantity", "annex-client-unit", "annex-client-unit-price", "annex-client-deducted", "annex-client-total-CHF",
                    "annex-client-subtotal", "annex-client-total", "annex-client-pied-page-g1", "annex-client-pied-page-g2", "annex-compte-titre1", "annex-compte-titre2",
                    "annex-compte-abrev-platform", "annex-compte-name-platform", "annex-compte-proj-no", "annex-compte-user", "annex-compte-start", "annex-compte-end",
                    "annex-compte-prestation", "annex-compte-quantity", "annex-compte-unit", "annex-compte-unit-price", "annex-compte-total-CHF", "annex-compte-subtotal",
                    "annex-compte-total", "annex-compte-pied-page-g1", "annex-compte-pied-page-g2", "res-factel", "res-pltf", "res-year", "res-month", "res-version",
                    "res-folder", "res-type", "info-created", "info-sent", "info-closed", "filigr-prof", "track-status", "track-doc-nr", "track-err-msg", "date",
                    "transac-nbr", "transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "total-subsid", "total-remb", "table-paramfact-0", "table-paramfact-1",
                    "table-paramfact-2", "table-paramfact-3", "table-articlesap-0", "table-articlesap-1", "table-articlesap-2", "table-articlesap-3", "table-articlesap-4",
                    "table-articlesap-5", "table-articlesap-6", "table-articlesap-7", "table-articlesap-8", "table-overhead-0", "table-overhead-1", "table-overhead-2", "table-base-0",
                    "table-base-1", "table-classeclient-0", "table-classeclient-1", "table-classeclient-2", "table-classeclient-3", "table-classeclient-4", "table-classeclient-5",
                    "table-classeclient-6", "table-classeclient-7", "table-classeclient-8", "table-plateforme-0", "table-plateforme-1", "table-plateforme-2", "table-partenaire-0",
                    "table-partenaire-1", "table-classeprestation-0", "table-classeprestation-1", "table-classeprestation-2", "table-classeprestation-3", "table-classeprestation-4",
                    "table-classeprestation-5", "table-classeprestation-6", "table-classeprestation-7", "table-classeprestation-8", "table-classeprestation-9", "table-categorie-0",
                    "table-categorie-1", "table-categorie-2", "table-categorie-3", "table-categorie-4", "table-categorie-5", "table-categorie-6", "table-groupe-0", "table-groupe-1",
                    "table-groupe-2", "table-groupe-3", "table-groupe-4", "table-groupe-5", "table-groupe-6", "table-groupe-7", "table-groupe-8", "table-coeffprestation-0",
                    "table-coeffprestation-1", "table-coeffprestation-2", "table-basecateg-0", "table-basecateg-1", "table-basecateg-2", "table-categprix-0", "table-categprix-1",
                    "table-categprix-2"];

    /**
     * Array containing the parameters, by specific keys
     *
     * @var array
     */
    private array $params;

    /**
     * Class constructor
     */
    function __construct()
    {
        $this->params = [];
        $lines = self::extract(CONFIG.self::NAME);
        foreach($lines as $line) {
            $this->params[$line[0]] = $line[1];
        }
    }

    /**
     * Gets the parameter for a specific key
     *
     * @param string $key specific key
     * @return string
     */
    function getParam(string $key): string
    {
        if(array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        else {
            return "";
        }
    }

    /**
     * Gets all parameters
     *
     * @return array
     */
    function getParams(): array
    {
        return $this->params;
    }

}
