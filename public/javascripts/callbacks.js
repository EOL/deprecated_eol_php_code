function callbacks()
{
    return {
        
        get_id: function(NODE) {
            var node = NODE.id;
            return node.replace("n","");
        },
        
        get_content: function(NODE) {
            var node = NODE.id;
            return $('#' + node + ' a:first').text();
        },
        
        get_metadata: function(NODE) {
            $.post("jstree_json.php", { "function" : "metadata", "id" : NODE.id }, function(data) { EDIT.details_panel(data); }, "json");
        },
        
        move_node: function(NODE, REF_NODE, TYPE)
        {
            switch(TYPE)
            {
                case "inside":
                    //may use REF_NODE.id as new parent
                    $.post("jstree_json.php", { "function" : "move_node", "id" : NODE.id, "child_of_id" : REF_NODE.id });
                    break;
            }
        },
        
        details_panel: function(data)
        {
            $('#node_title').html(data.node_title);
            $('#details_rank').val(data.node_rank);
        }
        
    };
}