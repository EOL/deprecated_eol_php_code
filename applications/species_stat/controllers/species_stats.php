<?php

/*
the CONTROLLER
*/

class species_stats_controller extends ControllerBase
{
    public static function index($parameters)
    {
        $group = @$parameters["group"];    
        render_template("species_stats/index",$group);
    }
    
    /*
    public static function results($parameters)
    {
        $tc_id = @$parameters["tc_id"];
        
        if($tc_id)
        {
            $model = new SpeciesStats();
            $stats = $model->stats_for_taxa(array($tc_id));
            //$stats = $model->stats_for_taxa(explode(",", $tc_id));
        }
        
        render_template("species_stats/results", array("stats" => $stats));
    }
    */
    
    
    public static function results($parameters)
    {
        $tc_id = @$parameters["tc_id"];
        $limit = @$parameters["limit"];
        $group = @$parameters["group"];
        
        if($group==1)
        {
            $model = new SpeciesStats();
            $stats = $model->taxa_stat(array($tc_id),$limit,$group);
            render_template("species_stats/results", array("stats" => $stats));
        }
        elseif($group==2)
        {
            $model = new SpeciesStats();
            $stats = $model->dataobject_stat(array($tc_id),$limit,$group);
            render_template("species_stats/results", array("stats" => $stats));        
        }
        elseif($group==3)
        {
            $model = new SpeciesStats();
            $stats = $model->links_stat($limit,$group);
            render_template("species_stats/results", array("stats" => $stats));        
        }                
        elseif($group==4)
        {
            $model = new SpeciesStats();
            $stats = $model->dataobject_stat_more($group);
            render_template("species_stats/results", array("stats" => $stats));        
        }                

    }    
}

?>