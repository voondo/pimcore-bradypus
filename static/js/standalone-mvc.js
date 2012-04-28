$(function(){

  var _resolvePath = function(name){
    return "../scripts/"+name;
  };

  var $layouts = $("bdp-layout"),
      _updateLayout = function(){
    var $this = $(this),
        key = _resolvePath($this.attr("key")),
        path_data = window.location.hash.substring(1).split("__"),
        path = null,
        instrs = tmp = [];

    $("bdp-partial").each(function(){
      var $this = $(this),
          path = _resolvePath($this.attr("path"));

      $.ajax({
        url: path,
        dataType: "html",
        success: function(html){
          $this.replaceWith(html)
        }
      });

    });

    path = path_data.shift();
    $.each(path_data, function(index, value){
      tmp = value.split("_");
      instrs.push(tmp);
    });

//     console.log(instrs);
    if(!path){
      path = "default/default.html"
    }

    path = _resolvePath(path);

    $.ajax({
      url: path,
      dataType: "text",
      success: function(html){
        $this.empty().html(html);

        $.each(instrs, function(index, value){
          str = decodeURIComponent((value[1] + '').replace(/\+/g, '%20'));
//           console.log(value);
          $(value[0], $this).text(str);
        });
      }
    });

  };
  $(window).bind("hashchange", function(){
    $layouts.each(_updateLayout);
  }).trigger("hashchange");
});


