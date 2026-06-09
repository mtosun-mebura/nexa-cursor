import './bootstrap';
import './chat';
import './admin-field-hints';
import '../css/admin-responsive.css';
import './admin-responsive';
import './admin-client-datatable';
import './website-page-seo';
import './website-page-field-panels';
import { bindAdminDropzoneClick, bindAdminUploadAreaClick, openAdminFilePicker } from './admin-image-dropzone';

window.openAdminFilePicker = openAdminFilePicker;
window.bindAdminDropzoneClick = bindAdminDropzoneClick;
window.bindAdminUploadAreaClick = bindAdminUploadAreaClick;