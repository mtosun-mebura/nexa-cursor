import React, {useCallback, useEffect, useMemo, useState} from 'react';
import {Player} from '@remotion/player';
import {AppTeaser} from '../compositions/AppTeaser';
import {ProductPromo} from '../compositions/ProductPromo';
import {
  APP_TEASER,
  DEFAULT_PROMO_PROPS,
  PRODUCT_PROMO,
} from '../lib/defaults';
import type {PromoVideoProps} from '../lib/types';

type CompositionKey = 'promo' | 'teaser';

function parsePropsFromSearch(): Partial<PromoVideoProps> & {
  composition?: CompositionKey;
} {
  const params = new URLSearchParams(window.location.search);
  const composition = params.get('composition') as CompositionKey | null;
  const raw = params.get('props');
  let fromJson: Partial<PromoVideoProps> = {};
  if (raw) {
    try {
      fromJson = JSON.parse(decodeURIComponent(raw));
    } catch {
      fromJson = {};
    }
  }

  const pick = (key: keyof PromoVideoProps): string | undefined => {
    const v = params.get(key);
    return v === null || v === '' ? undefined : v;
  };

  return {
    composition: composition === 'teaser' ? 'teaser' : 'promo',
    companyName: pick('companyName') ?? fromJson.companyName,
    productName: pick('productName') ?? fromJson.productName,
    tagline: pick('tagline') ?? fromJson.tagline,
    subtitle: pick('subtitle') ?? fromJson.subtitle,
    primaryColor: pick('primaryColor') ?? fromJson.primaryColor,
    accentColor: pick('accentColor') ?? fromJson.accentColor,
    backgroundColor: pick('backgroundColor') ?? fromJson.backgroundColor,
    ctaText: pick('ctaText') ?? fromJson.ctaText,
    websiteUrl: pick('websiteUrl') ?? fromJson.websiteUrl,
    logoUrl: pick('logoUrl') ?? fromJson.logoUrl,
    showAppScreens:
      params.get('showAppScreens') === '0'
        ? false
        : params.get('showAppScreens') === '1'
          ? true
          : fromJson.showAppScreens,
  };
}

export const PlayerApp: React.FC = () => {
  const initial = useMemo(() => parsePropsFromSearch(), []);
  const [composition, setComposition] = useState<CompositionKey>(
    initial.composition ?? 'promo',
  );
  const [props, setProps] = useState<PromoVideoProps>({
    ...DEFAULT_PROMO_PROPS,
    ...initial,
    showAppScreens: initial.showAppScreens ?? DEFAULT_PROMO_PROPS.showAppScreens,
  });

  const applyIncoming = useCallback((data: unknown) => {
    if (!data || typeof data !== 'object') return;
    const msg = data as {
      type?: string;
      composition?: CompositionKey;
      props?: Partial<PromoVideoProps>;
    };
    if (msg.type !== 'nexa-promo-props') return;
    if (msg.composition === 'promo' || msg.composition === 'teaser') {
      setComposition(msg.composition);
    }
    if (msg.props) {
      setProps((prev) => ({...prev, ...msg.props}));
    }
  }, []);

  useEffect(() => {
    const onMessage = (event: MessageEvent) => {
      applyIncoming(event.data);
    };
    window.addEventListener('message', onMessage);
    window.parent?.postMessage({type: 'nexa-promo-ready'}, '*');
    return () => window.removeEventListener('message', onMessage);
  }, [applyIncoming]);

  const meta = composition === 'teaser' ? APP_TEASER : PRODUCT_PROMO;
  const component = composition === 'teaser' ? AppTeaser : ProductPromo;

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#0b1220',
        padding: 12,
        boxSizing: 'border-box',
      }}
    >
      <Player
        component={component}
        inputProps={props}
        durationInFrames={meta.durationInFrames}
        compositionWidth={meta.width}
        compositionHeight={meta.height}
        fps={meta.fps}
        style={{
          width: '100%',
          maxHeight: '100%',
          aspectRatio: `${meta.width} / ${meta.height}`,
          borderRadius: 12,
          overflow: 'hidden',
        }}
        controls
        loop
        autoPlay={false}
        acknowledgeRemotionLicense
      />
    </div>
  );
};
