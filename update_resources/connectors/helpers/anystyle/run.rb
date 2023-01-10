require 'anystyle'
require 'json'

# pp AnyStyle.parse 'Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481.'
var = AnyStyle.parse ARGV
# var is an array in Ruby

# p var.instance_of? Fixnum     #=> True
# p var.instance_of? String     #=> True
# p var.instance_of? Array      #=> True - var is actually an array

json_var = var.to_json # convert array to json
pp json_var # return json

# how to run:
# ruby run.rb "Paul, C.R.C. and Smith, A.B., 1984. The early radiation and phylogeny of echinoderms. Biological Reviews, 59(4), pp.443-481. https://doi.org/10.1111/j.1469-185X.1984.tb00411.x"
