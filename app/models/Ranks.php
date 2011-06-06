<?php
namespace php_active_record;

class Rank extends ActiveRecord
{
    public static function species_ranks()
    {
        $species_ranks = array();
        $species_ranks[] = Rank::find_or_create_by_label('species');
        $species_ranks[] = Rank::find_or_create_by_label('sp');
        $species_ranks[] = Rank::find_or_create_by_label('sp.');
        $species_ranks[] = Rank::find_or_create_by_label('subspecies');
        $species_ranks[] = Rank::find_or_create_by_label('subsp');
        $species_ranks[] = Rank::find_or_create_by_label('subsp.');
        $species_ranks[] = Rank::find_or_create_by_label('variety');
        $species_ranks[] = Rank::find_or_create_by_label('var');
        $species_ranks[] = Rank::find_or_create_by_label('var.');
        $species_ranks[] = Rank::find_or_create_by_label('infraspecies');
        $species_ranks[] = Rank::find_or_create_by_label('form');
        $species_ranks[] = Rank::find_or_create_by_label('nothospecies');
        $species_ranks[] = Rank::find_or_create_by_label('nothosubspecies');
        $species_ranks[] = Rank::find_or_create_by_label('nothovariety');
        return $species_ranks;
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