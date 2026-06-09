/**
 * Website-pagina: SEO & AI-tekstgenerator (pagina-informatie).
 */

function websitePageSeoMetaEl() {
    return document.getElementById('website-page-seo-meta');
}

function websitePageSeoCsrfToken() {
    const meta = websitePageSeoMetaEl();
    if (meta?.dataset.csrf) {
        return meta.dataset.csrf;
    }
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
}

function updateMetaDescriptionLength() {
    const input = document.getElementById('meta_description');
    const counter = document.getElementById('meta-description-length');
    if (!input || !counter) {
        return;
    }
    const len = (input.value || '').length;
    const ideal = 160;
    counter.textContent = `${len} / ${ideal}`;
    counter.classList.toggle('text-destructive', len > ideal);
    counter.classList.toggle('text-success', len >= 120 && len <= ideal);
}

function setInputValue(id, value) {
    if (!value) {
        return;
    }
    const el = document.getElementById(id);
    if (el) {
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function setHeroField(sectionKey, field, value) {
    if (!value) {
        return;
    }
    const selector = `input[name="home_sections[${sectionKey}][${field}]"]`;
    const input = document.querySelector(selector);
    if (input) {
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        return;
    }
    const textarea = document.querySelector(`textarea[name="home_sections[${sectionKey}][${field}]"]`);
    if (textarea) {
        textarea.value = value;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

function setHeroSubtitle(sectionKey, value) {
    if (!value) {
        return;
    }
    const editorId = `hero-${sectionKey}-subtitle`;
    if (typeof window.setFlowbiteWysiwygContent === 'function') {
        window.setFlowbiteWysiwygContent(editorId, value);
        return;
    }
    setHeroField(sectionKey, 'subtitle', value);
}

function findFirstHeroSectionKey() {
    const card = document.querySelector('.home-section-card[data-section^="hero"]');
    return card ? card.getAttribute('data-section') : null;
}

function applyHeroSections(sections) {
    const hero = sections?.hero;
    if (!hero || typeof hero !== 'object') {
        return;
    }
    const sectionKey = findFirstHeroSectionKey();
    if (!sectionKey) {
        return;
    }
    setHeroField(sectionKey, 'title', hero.title);
    setHeroSubtitle(sectionKey, hero.subtitle);
    setHeroField(sectionKey, 'cta_primary_text', hero.cta_primary_text);
    setHeroField(sectionKey, 'cta_secondary_text', hero.cta_secondary_text);
}

function renderSeoTips(tips) {
    const box = document.getElementById('website-page-seo-tips');
    if (!box || !Array.isArray(tips) || tips.length === 0) {
        return;
    }
    box.innerHTML = tips.map((t) => `<p class="mb-0">• ${escapeHtml(String(t))}</p>`).join('');
    box.classList.remove('hidden');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function collectSeoPayload() {
    const form = document.getElementById('website-page-form');
    const moduleHidden = document.getElementById('module_name_hidden');
    const companyInput = form?.querySelector('input[name="company_id"]');
    const companySelect = form?.querySelector('select[name="company_id"]');

    let companyId = companyInput?.value || '';
    if (!companyId && companySelect) {
        companyId = companySelect.value;
    }

    return {
        title: document.getElementById('title')?.value?.trim() || '',
        page_type: document.getElementById('page_type')?.value || 'custom',
        module_name: moduleHidden?.value || '',
        slug: document.getElementById('slug')?.value?.trim() || '',
        company_id: companyId ? parseInt(companyId, 10) : null,
        include_sections: document.getElementById('website-page-seo-apply-sections')?.checked !== false,
    };
}

async function generateWebsitePageSeo() {
    const meta = websitePageSeoMetaEl();
    const btn = document.getElementById('website-page-seo-generate-btn');
    if (!meta?.dataset.generateUrl || !btn) {
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="opacity-80">Bezig…</span>';

    try {
        const res = await fetch(meta.dataset.generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': websitePageSeoCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(collectSeoPayload()),
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok || !json.data) {
            const msg = json.message || 'Genereren mislukt. Probeer opnieuw.';
            window.alert(msg);
            return;
        }

        const data = json.data;
        if (data.title) {
            setInputValue('title', data.title);
        }
        if (data.meta_description) {
            setInputValue('meta_description', data.meta_description);
            updateMetaDescriptionLength();
        }
        if (collectSeoPayload().include_sections) {
            applyHeroSections(data.sections);
        }
        renderSeoTips(data.tips);

        const sourceNote = data.source === 'openai' ? ' (AI)' : '';
        btn.title = `Laatst gegenereerd${sourceNote}`;
    } catch (err) {
        console.error('[website-page-seo]', err);
        window.alert('Genereren mislukt door een netwerkfout.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

function initWebsitePageSeo() {
    const form = document.getElementById('website-page-form');
    if (!form) {
        return;
    }

    const btn = document.getElementById('website-page-seo-generate-btn');
    if (btn) {
        btn.addEventListener('click', generateWebsitePageSeo);
    }

    const metaInput = document.getElementById('meta_description');
    if (metaInput) {
        metaInput.addEventListener('input', updateMetaDescriptionLength);
        updateMetaDescriptionLength();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWebsitePageSeo);
} else {
    initWebsitePageSeo();
}
