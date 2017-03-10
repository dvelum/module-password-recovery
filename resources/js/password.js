$(document).ready(function () {
    var msgBox = $('#msgbox'),
        message = function(type, msg) {
            msgBox.removeClass().addClass(type == 'success'
                ? 'alert alert-success' :
                type == 'failure' ? 'alert alert-danger' : '');
            msgBox.text(msg);
        },
        submitHandler = function (form, event) {
            event.preventDefault();
            var params = getParams(form);
            message('wait', '...');
            $.ajax(params).done(function(data) {
                data = $.parseJSON(data);
                if (data.success) {
                    message('success', data.data.msg);
                    $(form).data('collapsible') && $(form).parent().detach();
                } else {
                    message('failure', data.msg);
                }
            });
            return false;
        },
        getParams = function(form) {
            var form = $(form), data = {};
            $.each(form.serializeArray(), function(i, v) {
                data[v.name] = v.value;
            });
            return {
                'url': form.attr('action') || '',
                'type': form.attr('method') || 'POST',
                'data': data
            };
        };
    $.each(['#passForm', '#newPassForm'], function(i, form) {
        $(form).submit(function(event) {
            submitHandler(form, event);
        });
    });
});
