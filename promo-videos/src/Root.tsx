import React from 'react';
import {Composition} from 'remotion';
import {AppTeaser} from './compositions/AppTeaser';
import {ProductPromo} from './compositions/ProductPromo';
import {
  APP_TEASER,
  DEFAULT_PROMO_PROPS,
  PRODUCT_PROMO,
} from './lib/defaults';

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition
        id={PRODUCT_PROMO.id}
        component={ProductPromo}
        durationInFrames={PRODUCT_PROMO.durationInFrames}
        fps={PRODUCT_PROMO.fps}
        width={PRODUCT_PROMO.width}
        height={PRODUCT_PROMO.height}
        defaultProps={DEFAULT_PROMO_PROPS}
      />
      <Composition
        id={APP_TEASER.id}
        component={AppTeaser}
        durationInFrames={APP_TEASER.durationInFrames}
        fps={APP_TEASER.fps}
        width={APP_TEASER.width}
        height={APP_TEASER.height}
        defaultProps={DEFAULT_PROMO_PROPS}
      />
    </>
  );
};
