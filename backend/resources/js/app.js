import './bootstrap';
import './chat';
import './admin-field-hints';
import { bindAdminDropzoneClick, bindAdminUploadAreaClick, openAdminFilePicker } from './admin-image-dropzone';

window.openAdminFilePicker = openAdminFilePicker;
window.bindAdminDropzoneClick = bindAdminDropzoneClick;
window.bindAdminUploadAreaClick = bindAdminUploadAreaClick;