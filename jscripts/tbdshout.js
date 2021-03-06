/*!
* tbdshout.js - TBD Shout
* Copyright (C) 2015 Suhaimi Amir <suhaimi@tbd.my>
* Licensed under GNU General Public License v3
*/

var tbdshoutApp = angular.module('tbdshoutApp', ['ngWebSocket','yaru22.angular-timeago']);

tbdshoutApp.controller('shoutCtrl', ['$scope', '$sce', '$http','$websocket', function ($scope,$sce,$http,$websocket){

  var smiley_data = {},msgcol = [],udata,status_arr = {1:'',2:'Connecting...',0:'Disconnected',3:'Reconnecting...'}
  var max_msg_row = 30;
  var reconnect_time = 2000;

  var statusChange = function(status) {
    $scope.status = status;
    $scope.status_txt = status_arr[status];
  };

  var linky = function(text) {
    var urlPattern = /(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/gi;
    return text.replace(urlPattern, '<a target="_blank" href="$&">$&</a>');
  };

  var smiley = function() {
    return {
      set: function(data) {
        smiley_data = data;
      },
      parse: function (msg) {
        var ret_msg = ""; var words = msg.split(" ");
        for (var i = 0; i < words.length; i++) {
          if(smiley_data.hasOwnProperty(words[i])){
            ret_msg += " <img style=\"vertical-align: middle;\" border=\"0\" class='smilies' src='"+ smiley_data[words[i]].img +"'>";
          }else{
            ret_msg += ' ' + words[i];
          }
        }
        return ret_msg;
      }
    };
  };

  var reconnect = function() {

    setTimeout(function(){
      console.log('Connection lost, reconnect in ' + parseInt(reconnect_time/1000) + 'sec');
      reconnect_time = reconnect_time*2;
      statusChange(3);
      console.log('Reconnecting...');
      ws.reconnect();
    }, reconnect_time);
  };

  var formatMsg = function(msg) {
    return smiley().parse(linky(msg));
  };

  var start = function(callback) {
    statusChange(0);
    $http.get('xmlhttp.php?action=tbdshout_get').success(function(data) {

      smiley().set(data.smiley);

      if (data.msg) {
        for (var i in data.msg) {
          data.msg[i].msg = formatMsg(data.msg[i].msg);
          msgcol.push(data.msg[i]);
        }
      }

      $scope.sr_tinggi_kotak = { 'height': data.max_height + 'px'};
      udata = data;
      max_msg_row = data.max_msg;

      if (data.channel !== '' && data.skey !== '') {
        callback();
      }
    });
  };

  start(function() {
    statusChange(2);
    ws = $websocket('wss://chat.tbd.my/con/' + udata.skey + '/' + udata.channel);

    ws.onMessage(function(data) {
      data = angular.fromJson(data.data);

      if (data.name === '') { return; }
      if (data.uid < 1) { return; }

      var msg = linky(data.msg);

      if (angular.equals(data.channel,udata.channel)) {
        msgcol.unshift({
          name:data.name,
          avatar: data.avatar,
          msg: formatMsg(data.msg),
          date: (new Date()).getTime(),
        });
        if (msgcol.length >= max_msg_row) {
          msgcol.pop();
        }
      }
    });

    ws.onClose(function(data) {
      statusChange(0);
      reconnect();
    });

    ws.onOpen(function(data) {
      statusChange(1);
      //console.log('connected!');
    });
  });

  $scope.msgRows = msgcol;

  $scope.tawakalJela = function(data) {
    return $sce.trustAsHtml(data);
  };

  $scope.sendMsg = function() {
    ws.send(JSON.stringify({name:udata.name,uid:udata.uid,msg:$scope.shoutText,key:udata.ukey,channel:udata.channel,avatar:udata.avatar}));
    $scope.shoutText = '';
  };

}]);
