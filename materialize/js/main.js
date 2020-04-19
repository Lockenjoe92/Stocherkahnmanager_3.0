$(document).ready(function(){
  $( function() {
    $( "#add_qualification" ).sisyphus();
    $( "#quali_edit_form" ).sisyphus();
    $( "#add_einsatzkategorie" ).sisyphus();
    $( "#einsatzkategorie_edit_form" ).sisyphus();
    $( "#add_location" ).sisyphus();
    $( "#locations_edit_form" ).sisyphus();
    $( "#add_aufenthaltsort" ).sisyphus();
    $( "#aufenthaltsort_edit_form" ).sisyphus();
  });

  $('.datepicker').datepicker({
    autoClose: true,
    yearRange: [1900,2022],
    setDefaultDate: true,
    i18n: {
      today: 'Heute',
	    cancel: 'Schließen',
	    clear: 'Zurücksetzen',
	    done: 'OK',
	    nextMonth: 'Nächster Monat',
	    previousMonth: 'Vorheriger Monat',
	    months: [ 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ],
	    monthsShort: [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
	    weekdays: [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' ],
	    weekdaysShort: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
	    weekdaysAbbrev: [ 'S', 'M', 'D', 'M', 'D', 'F', 'S' ],
	  },
	  format: 'dd.mm.yyyy',
	  firstDay: 1
  });
  
  $('.timepicker').timepicker({
    twelveHour: false,
    i18n: {
      cancel: 'Schließen',
	    done: 'OK'
	  }
  });
  
   $('#nodate').change(function(){
    if( $('#nodate').is(':checked') ) {
      $('#endDate').val('31.12.9999').prop('readonly', true);
      $('#endTime').val('23:59').prop('readonly', true);
    } else {
      $('#endDate').val('').prop('readonly', false);
      $('#endTime').val('').prop('readonly', false);
    }
   });
});
