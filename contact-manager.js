jQuery(document).ready(function($) {
    $('.edit-message').click(function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const email = $(this).data('email');
        const message = $(this).data('message');

        $('#edit-modal input[name="id"]').val(id);
        $('#edit-modal input[name="email"]').val(email);
        $('#edit-modal textarea[name="message"]').val(message);

        $('#edit-modal').show();
    });

    $('#close-modal').click(function() {
        $('#edit-modal').hide();
    });

    $('#update-message-form').submit(function(e) {
        e.preventDefault();

        const email = $('#edit-modal input[name="email"]').val();
        const message = $('#edit-modal textarea[name="message"]').val();

        if (!email && !message) {
            $('#form-status').text('Both email and message are missing.').css('color', 'red');
        } else if(!message){
            $('#form-status').text('Message is missing.').css('color', 'red');
        } else if(!email){
            $('#form-status').text('Email is missing.').css('color', 'red');
        }

        const formData = $(this).serialize();

        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: formData + '&action=update_message' + '&security=' + ajax_object.nonce,
            success: function(response) {
                if (response.success) {
                    $('#edit-modal').hide();
                    $('#successful').show();
                        $('#close-success').click(function() {
                            $('#successful').hide();
                            window.location.reload();
                        });
                    
                } else {
                    $('#form-status').text(response.data.message).css('color', 'red');
                }
            },
            error: function() {
                $('#form-status').text('Something went wrong. Please try again.').css('color', 'red');
            }
        });
    });
});

jQuery(document).ready(function($) {
    let deleteForm;

    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        deleteForm = $(this).closest('form');
        $('#delete-modal').show();
    });

    $('#cancel-delete').click(function() {
        $('#delete-modal').hide();
    });

    $('#confirm-delete').click(function() {
        deleteForm.submit();
        $('#delete-modal').hide();
    });
});
