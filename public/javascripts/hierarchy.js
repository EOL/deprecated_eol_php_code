
$(function() {
    TREE = new tree_component();
    EDIT = new callbacks();
    host = window.location.hostname;
    
    TREE.init($("#hierarchy"), {
        data : { 
            type    : "json",
            async   : true,
            url     : "http://localhost/eol_php_code/temp/jstree_json.php"
            },
        lang : {
            new_node    : "Taxon",
            loading     : "&nbsp;&nbsp;&nbsp;&nbsp;"
            },
        ui : {
            dots        : true,
            rtl         : false,
            hover_mode  : false
            },
        rules : {
                type_attr   : "rel",
                multiple    : "ctrl",
                createat    : "top",
                multitree   : true,
                metadata    : false,
                use_inline  : false,
                clickable   : "all",
                renameable  : "all",
                draggable   : "all",
                createable  : "all",
                dragrules   : "all"
            },
        callback : {
            
            onmove : function(NODE, REF_NODE, TYPE, TREE_OBJ) { EDIT.move_node(NODE,REF_NODE,TYPE); },
            
            ondclk : function(NODE, TREE_OBJ) {
                    TREE_OBJ.toggle_branch.call(TREE_OBJ, NODE);
                    TREE_OBJ.select_branch.call(TREE_OBJ, NODE);
                },
            
            ondblclk : function(NODE, TREE_OBJ) { EDIT.get_metadata(NODE); }
            }
    });
});