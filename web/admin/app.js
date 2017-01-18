$(document).ready(function() {
    $('#form_titre').on('input', function() {
        $('#form_slug').val(slugify($(this).val()));
    });

    $(document).on ("click", "#delete", function (e) {
        if(!confirm("Voulez vous vraiment supprimer le contenu?")) {
            e.preventDefault();
        }
    });

    tinymce.init({
        selector: '#form_contenu',
        toolbar: 'bold | italic | underline | strikethrough | alignleft | aligncenter | alignright | alignjustify | formatselect | bullist | numlist | outdent | indent | blockquote | undo | redo | removeformat',
        menubar: '',
        statusbar: false,
        height: 480
    });
});

function slugify(text)
{
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
}