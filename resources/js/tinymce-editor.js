// TinyMCE tự lưu trữ (GPL-2.0-or-later), chỉ tải ở trang có trình soạn bài viết.
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide-dark/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/ui/oxide-dark/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/skins/content/dark/content.js';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import 'tinymce-i18n/langs7/vi.js';

const dark = () => document.documentElement.classList.contains('dark');

// Định dạng bài viết trên website (khớp với typography trang Kiến thức) để soạn thấy gần giống khi đăng.
const articleCss = `
body { font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.7; max-width: 760px; margin: 1rem auto; padding: 0 1rem; }
h2 { font-size: 1.5rem; margin: 1.6em 0 .6em; } h3 { font-size: 1.25rem; margin: 1.4em 0 .5em; } h4 { font-size: 1.05rem; }
blockquote { border-left: 4px solid #34d399; margin: 1em 0; padding: .25em 1em; color: inherit; opacity: .85; }
table { border-collapse: collapse; width: 100%; } td, th { border: 1px solid #cbd5e1; padding: .4em .6em; }
img { max-width: 100%; height: auto; }
`;

function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

window.EzfinTiny = {
    mount(component) {
        const target = component.$refs.textarea;
        if (!target || target.dataset.mounted) return;
        target.dataset.mounted = '1';
        const opts = component.options;

        tinymce.init({
            target,
            license_key: 'gpl',
            language: 'vi',
            height: opts.height,
            placeholder: opts.placeholder || '',
            readonly: component.disabled,
            skin: dark() ? 'oxide-dark' : 'oxide',
            content_css: dark() ? 'dark' : 'default',
            content_style: articleCss,
            ui_mode: 'split',
            promotion: false,
            branding: false,
            menubar: 'edit view insert format table tools',
            plugins: 'advlist anchor autolink charmap code fullscreen image link lists nonbreaking preview quickbars searchreplace table visualblocks wordcount',
            toolbar: [
                'undo redo | blocks fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link unlink anchor | image table blockquote hr charmap nonbreaking | subscript superscript removeformat | searchreplace visualblocks code preview fullscreen',
            ],
            toolbar_mode: 'wrap',
            block_formats: 'Đoạn văn=p; Tiêu đề 2=h2; Tiêu đề 3=h3; Tiêu đề 4=h4; Trích dẫn=blockquote; Mã nguồn=pre',
            font_size_formats: '12px 14px 16px 18px 20px 24px 28px',
            quickbars_insert_toolbar: false,
            quickbars_selection_toolbar: 'bold italic underline | h2 h3 blockquote | link',
            link_default_target: '_blank',
            link_assume_external_targets: 'https',
            link_target_list: [{ title: 'Cùng trang', value: '' }, { title: 'Tab mới', value: '_blank' }],
            rel_list: [{ title: 'Không', value: '' }, { title: 'nofollow noopener', value: 'nofollow noopener' }, { title: 'sponsored nofollow noopener', value: 'sponsored nofollow noopener' }],
            image_caption: true,
            image_advtab: true,
            image_title: true,
            automatic_uploads: true,
            relative_urls: false,
            convert_urls: false,
            paste_data_images: opts.canUpload,
            file_picker_types: opts.canUpload ? 'image' : '',
            images_file_types: 'png,jpg,jpeg,webp',
            images_upload_handler: (blobInfo) => {
                if (!opts.canUpload) return Promise.reject('Không cho phép tải ảnh.');
                const blob = blobInfo.blob();
                const file = blob instanceof File ? blob : new File([blob], blobInfo.filename(), { type: blob.type });
                if (opts.acceptedTypes && !opts.acceptedTypes.includes(file.type)) {
                    return Promise.reject('Chỉ nhận ảnh PNG, JPG hoặc WebP.');
                }
                if (opts.maxSizeKb && file.size > opts.maxSizeKb * 1024) {
                    return Promise.reject(`Ảnh vượt quá ${Math.round(opts.maxSizeKb / 1024)} MB.`);
                }
                return component.upload(file);
            },
            file_picker_callback: (callback) => {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = (opts.acceptedTypes || ['image/png', 'image/jpeg', 'image/webp']).join(',');
                input.addEventListener('change', () => {
                    const file = input.files[0];
                    if (!file) return;
                    component.upload(file).then((url) => callback(url, { alt: file.name.replace(/\.[^.]+$/, '') }))
                        .catch((message) => tinymce.activeEditor?.notificationManager.open({ text: message, type: 'error' }));
                });
                input.click();
            },
            setup(editor) {
                component.editor = editor;
                const push = debounce(() => {
                    const html = editor.getContent();
                    if (html !== (component.state ?? '')) component.state = html;
                }, 300);

                editor.on('init', () => editor.setContent(component.state ?? ''));
                editor.on('input change undo redo keyup ExecCommand SetContent', push);
                editor.on('blur', () => { component.state = editor.getContent(); });

                component.$watch('state', (value) => {
                    if (!editor.initialized) return;
                    if ((value ?? '') !== editor.getContent()) editor.setContent(value ?? '');
                });
            },
        });
    },

    unmount(component) {
        component.editor?.remove();
        component.editor = null;
    },
};
