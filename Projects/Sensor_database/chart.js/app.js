$(document).ready(function(){
  $.ajax({
   url: "https://nickkaemer.com/Sensor_data.php",
    method: "GET",
    success: function(data) {
      console.log(data);
      var x_axis = []; // a generic variable
      var y_axis = [];

      for(var i in data) {
        x_axis.push("N:" + data[i].time_received); // must match your dBase columns
        y_axis.push(data[i].temperature);
      }

      var chartdata = {
        labels: x_axis,
        datasets : [
          {
            label: 'Temperature', //Title
            // Change colors: https://www.w3schools.com/css/tryit.asp?filename=trycss3_color_rgba 
            backgroundColor: 'rgba(255, 99, 132, 0.75)', 
            borderColor: 'rgba(255, 99, 132, 0.75)', 
            hoverBackgroundColor: 'rgba(255, 99, 132, 0.75)',
            hoverBorderColor: 'rgba(255, 99, 132, 0.75)',
            data: y_axis
          }
        ]
      };

      var ctx = $("#mycanvas");

      var barGraph = new Chart(ctx, {
        type: 'bar',   //Chart Type 
        data: chartdata
      });
    },
    error: function(data) {
      console.log(data);
    }
  });
});
