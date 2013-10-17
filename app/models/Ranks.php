<?php
namespace php_active_record;

class Rank extends ActiveRecord
{
    public static $has_many = array(
            array('language')
        );
    
    public static function species_ranks()
    {
        $species_ranks = array();
        $species_ranks[] = Rank::find_or_create_by_translated_label('species');
        $species_ranks[] = Rank::find_or_create_by_translated_label('sp');
        $species_ranks[] = Rank::find_or_create_by_translated_label('sp.');
        $species_ranks[] = Rank::find_or_create_by_translated_label('subspecies');
        $species_ranks[] = Rank::find_or_create_by_translated_label('subsp');
        $species_ranks[] = Rank::find_or_create_by_translated_label('subsp.');
        $species_ranks[] = Rank::find_or_create_by_translated_label('variety');
        $species_ranks[] = Rank::find_or_create_by_translated_label('var');
        $species_ranks[] = Rank::find_or_create_by_translated_label('var.');
        $species_ranks[] = Rank::find_or_create_by_translated_label('infraspecies');
        $species_ranks[] = Rank::find_or_create_by_translated_label('form');
        $species_ranks[] = Rank::find_or_create_by_translated_label('nothospecies');
        $species_ranks[] = Rank::find_or_create_by_translated_label('nothosubspecies');
        $species_ranks[] = Rank::find_or_create_by_translated_label('nothovariety');
        return $species_ranks;
    }

    public static function kingdom_rank_ids()
    {
        $rank_ids = array();
        $rank_labels = array('kingdom', 'regn', 'regn.');
        foreach($rank_labels as $rank_label)
        {
            if($rank = Rank::find_or_create_by_translated_label($rank_label)) $rank_ids[] = $rank->id;
        }
        return $rank_ids;
    }

    public static function phylum_rank_ids()
    {
        $rank_ids = array();
        $rank_labels = array('phylum', 'phyl', 'phyl.');
        foreach($rank_labels as $rank_label)
        {
            if($rank = Rank::find_or_create_by_translated_label($rank_label)) $rank_ids[] = $rank->id;
        }
        return $rank_ids;
    }

    public static function class_rank_ids()
    {
        $rank_ids = array();
        $rank_labels = array('class', 'cl', 'cl.');
        foreach($rank_labels as $rank_label)
        {
            if($rank = Rank::find_or_create_by_translated_label($rank_label)) $rank_ids[] = $rank->id;
        }
        return $rank_ids;
    }

    public static function order_rank_ids()
    {
        $rank_ids = array();
        $rank_labels = array('order', 'ord', 'ord.');
        foreach($rank_labels as $rank_label)
        {
            if($rank = Rank::find_or_create_by_translated_label($rank_label)) $rank_ids[] = $rank->id;
        }
        return $rank_ids;
    }

    public static function family_rank_ids()
    {
        $rank_ids = array();
        $rank_labels = array('family', 'fam', 'fam.');
        foreach($rank_labels as $rank_label)
        {
            if($rank = Rank::find_or_create_by_translated_label($rank_label)) $rank_ids[] = $rank->id;
        }
        return $rank_ids;
    }

    public static function species_ranks_ids()
    {
        $species_ranks_ids = array();
        $species_ranks = self::species_ranks();
        foreach($species_ranks as $rank)
        {
            $species_ranks_ids[] = $rank->id;
        }
        return $species_ranks_ids;
    }
}

?>