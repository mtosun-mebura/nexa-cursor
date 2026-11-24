import React from 'react';
import {
  AbsoluteFill,
  Img,
  Sequence,
  interpolate,
  spring,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {BrandMark} from '../components/BrandMark';
import {FeatureSlide} from '../components/FeatureSlide';
import {PhoneFrame} from '../components/PhoneFrame';
import {SceneBackground} from '../components/SceneBackground';
import {FEATURES} from '../lib/defaults';
import type {PromoVideoProps} from '../lib/types';

const Intro: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 16, stiffness: 100}});
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const y = interpolate(enter, [0, 1], [40, 0]);

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          justifyContent: 'center',
          alignItems: 'center',
          opacity,
          transform: `translateY(${y}px)`,
        }}
      >
        <BrandMark
          logoUrl={props.logoUrl}
          companyName={props.companyName}
          accentColor={props.accentColor}
          size={96}
        />
        <p
          style={{
            marginTop: 36,
            fontSize: 28,
            color: '#9aa8bd',
            letterSpacing: '0.18em',
            textTransform: 'uppercase',
            fontWeight: 600,
          }}
        >
          Product promo
        </p>
      </AbsoluteFill>
    </SceneBackground>
  );
};

const Hero: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 18, stiffness: 110}});
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const x = interpolate(enter, [0, 1], [60, 0]);

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          padding: 96,
          display: 'flex',
          flexDirection: 'row',
          alignItems: 'center',
          gap: 56,
          opacity,
        }}
      >
        <div style={{flex: 1.1, transform: `translateX(${-x}px)`}}>
          <div
            style={{
              display: 'inline-block',
              padding: '10px 18px',
              borderRadius: 999,
              background: `${props.accentColor}22`,
              color: props.accentColor,
              fontWeight: 700,
              fontSize: 22,
              marginBottom: 28,
            }}
          >
            {props.productName}
          </div>
          <h1
            style={{
              fontSize: 68,
              lineHeight: 1.08,
              margin: '0 0 28px',
              fontWeight: 800,
              maxWidth: 820,
            }}
          >
            {props.tagline}
          </h1>
          <p style={{fontSize: 32, color: '#9aa8bd', margin: 0, maxWidth: 700}}>
            {props.subtitle}
          </p>
        </div>
        <div style={{flex: 1, transform: `translateX(${x}px)`}}>
          <Img
            src={staticFile('images/hero-nexa-platform.png')}
            style={{
              width: '100%',
              borderRadius: 28,
              boxShadow: '0 30px 90px rgba(0,0,0,0.5)',
            }}
          />
        </div>
      </AbsoluteFill>
    </SceneBackground>
  );
};

const AppShowcase: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 16, stiffness: 100}});
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const y = interpolate(enter, [0, 1], [50, 0]);

  const phones = [
    'images/feature-chauffeur-app.png',
    'images/feature-taxi-booking.png',
    'images/feature-chauffeur-navigatie-map.png',
  ];

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          padding: 80,
          opacity,
          transform: `translateY(${y}px)`,
        }}
      >
        <h2
          style={{
            textAlign: 'center',
            fontSize: 56,
            fontWeight: 800,
            margin: '0 0 16px',
          }}
        >
          De app in actie
        </h2>
        <p
          style={{
            textAlign: 'center',
            fontSize: 28,
            color: '#9aa8bd',
            margin: '0 0 56px',
          }}
        >
          Boeken, dispatchen en rijden — één merk, overal
        </p>
        <div
          style={{
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'flex-end',
            gap: 48,
          }}
        >
          {phones.map((src, i) => {
            const delay = i * 6;
            const local = spring({
              frame: frame - delay,
              fps,
              config: {damping: 14, stiffness: 120},
            });
            const scale = interpolate(local, [0, 1], [0.85, i === 1 ? 1.05 : 0.95]);
            return (
              <div key={src} style={{transform: `scale(${scale})`, opacity: local}}>
                <PhoneFrame
                  src={src}
                  width={i === 1 ? 340 : 300}
                  accentColor={props.accentColor}
                />
              </div>
            );
          })}
        </div>
      </AbsoluteFill>
    </SceneBackground>
  );
};

const CtaEnd: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 14, stiffness: 110}});
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const scale = interpolate(enter, [0, 1], [0.9, 1]);

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          justifyContent: 'center',
          alignItems: 'center',
          opacity,
          transform: `scale(${scale})`,
          textAlign: 'center',
          padding: 96,
        }}
      >
        <BrandMark
          logoUrl={props.logoUrl}
          companyName={props.companyName}
          accentColor={props.accentColor}
          size={80}
        />
        <h2
          style={{
            fontSize: 64,
            fontWeight: 800,
            margin: '48px 0 20px',
            maxWidth: 1000,
          }}
        >
          {props.ctaText}
        </h2>
        <p style={{fontSize: 34, color: '#9aa8bd', margin: '0 0 40px'}}>
          {props.websiteUrl}
        </p>
        <div
          style={{
            padding: '22px 48px',
            borderRadius: 999,
            background: `linear-gradient(135deg, ${props.primaryColor}, ${props.accentColor})`,
            fontSize: 28,
            fontWeight: 700,
            boxShadow: `0 16px 40px ${props.accentColor}55`,
          }}
        >
          Plan een demo
        </div>
      </AbsoluteFill>
    </SceneBackground>
  );
};

export const ProductPromo: React.FC<PromoVideoProps> = (props) => {
  const featureDuration = 150;
  const featureStart = 240;

  return (
    <AbsoluteFill style={{backgroundColor: props.backgroundColor}}>
      <Sequence from={0} durationInFrames={90}>
        <Intro {...props} />
      </Sequence>
      <Sequence from={90} durationInFrames={150}>
        <Hero {...props} />
      </Sequence>
      {FEATURES.map((feature, index) => (
        <Sequence
          key={feature.title}
          from={featureStart + index * featureDuration}
          durationInFrames={featureDuration}
        >
          <FeatureSlide
            title={feature.title}
            body={feature.body}
            image={feature.image}
            primaryColor={props.primaryColor}
            accentColor={props.accentColor}
            backgroundColor={props.backgroundColor}
            index={index}
            usePhone={props.showAppScreens && index === 2}
          />
        </Sequence>
      ))}
      <Sequence from={840} durationInFrames={240}>
        <AppShowcase {...props} />
      </Sequence>
      <Sequence from={1080} durationInFrames={270}>
        <CtaEnd {...props} />
      </Sequence>
    </AbsoluteFill>
  );
};
