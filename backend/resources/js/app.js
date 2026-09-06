import './bootstrap';
import './chat';
import './admin-field-hints';
import '../css/admin-responsive.css';
import './nexa-smooth-accordion';
import './admin-responsive';
import './admin-date-picker';
import './admin-address-autocomplete';
import './admin-client-datatable';
import './admin-email-copy';
import './website-page-seo';
import './website-page-field-panels';
import './ai-image-drop-bridge';
import { bindAdminDropzoneClick, bindAdminUploadAreaClick, openAdminFilePicker } from './admin-image-dropzone';

window.openAdminFilePicker = openAdminFilePicker;
window.bindAdminDropzoneClick = bindAdminDropzoneClick;
window.bindAdminUploadAreaClick = bindAdminUploadAreaClick;