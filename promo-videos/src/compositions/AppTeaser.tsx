import React from 'react';
import {
  AbsoluteFill,
  Sequence,
  interpolate,
  spring,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {BrandMark} from '../components/BrandMark';
import {PhoneFrame} from '../components/PhoneFrame';
import {SceneBackground} from '../components/SceneBackground';
import type {PromoVideoProps} from '../lib/types';

const TeaserIntro: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 14, stiffness: 120}});
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const y = interpolate(enter, [0, 1], [30, 0]);

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          justifyContent: 'center',
          alignItems: 'center',
          padding: 64,
          opacity,
          transform: `translateY(${y}px)`,
          textAlign: 'center',
        }}
      >
        <BrandMark
          logoUrl={props.logoUrl}
          companyName={props.companyName}
          accentColor={props.accentColor}
          size={88}
        />
        <h1
          style={{
            fontSize: 64,
            fontWeight: 800,
            lineHeight: 1.15,
            margin: '48px 0 20px',
            maxWidth: 820,
          }}
        >
          {props.productName}
        </h1>
        <p style={{fontSize: 32, color: '#9aa8bd', margin: 0, maxWidth: 720}}>
          {props.tagline}
        </p>
      </AbsoluteFill>
    </SceneBackground>
  );
};

const TeaserPhones: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 16, stiffness: 100}});

  const screens = [
    'images/feature-taxi-booking.png',
    'images/feature-chauffeur-app.png',
  ];

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          justifyContent: 'center',
          alignItems: 'center',
          padding: 48,
          opacity: enter,
        }}
      >
        <p
          style={{
            fontSize: 28,
            fontWeight: 700,
            color: props.accentColor,
            marginBottom: 36,
            letterSpacing: '0.12em',
            textTransform: 'uppercase',
          }}
        >
          Mobile app
        </p>
        <div style={{display: 'flex', gap: 36, alignItems: 'flex-end'}}>
          {screens.map((src, i) => {
            const local = spring({
              frame: frame - i * 8,
              fps,
              config: {damping: 14, stiffness: 110},
            });
            const y = interpolate(local, [0, 1], [80, 0]);
            return (
              <div
                key={src}
                style={{
                  transform: `translateY(${y}px) scale(${i === 0 ? 1 : 0.92})`,
                  opacity: local,
                }}
              >
                <PhoneFrame
                  src={src}
                  width={i === 0 ? 380 : 320}
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

const TeaserCta: React.FC<PromoVideoProps> = (props) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = spring({frame, fps, config: {damping: 14, stiffness: 120}});
  const scale = interpolate(enter, [0, 1], [0.88, 1]);

  return (
    <SceneBackground {...props}>
      <AbsoluteFill
        style={{
          justifyContent: 'center',
          alignItems: 'center',
          textAlign: 'center',
          padding: 64,
          opacity: enter,
          transform: `scale(${scale})`,
        }}
      >
        <h2
          style={{
            fontSize: 58,
            fontWeight: 800,
            margin: '0 0 28px',
            maxWidth: 800,
            lineHeight: 1.15,
          }}
        >
          {props.ctaText}
        </h2>
        <div
          style={{
            padding: '22px 44px',
            borderRadius: 999,
            background: `linear-gradient(135deg, ${props.primaryColor}, ${props.accentColor})`,
            fontSize: 30,
            fontWeight: 700,
            marginBottom: 28,
          }}
        >
          {props.websiteUrl}
        </div>
        <p style={{fontSize: 26, color: '#9aa8bd', margin: 0}}>
          {props.subtitle}
        </p>
      </AbsoluteFill>
    </SceneBackground>
  );
};

/** Vertical 15s teaser for social (Stories / Reels / TikTok). */
export const AppTeaser: React.FC<PromoVideoProps> = (props) => {
  return (
    <AbsoluteFill style={{backgroundColor: props.backgroundColor}}>
      <Sequence from={0} durationInFrames={120}>
        <TeaserIntro {...props} />
      </Sequence>
      <Sequence from={120} durationInFrames={210}>
        <TeaserPhones {...props} />
      </Sequence>
      <Sequence from={330} durationInFrames={120}>
        <TeaserCta {...props} />
      </Sequence>
    </AbsoluteFill>
  );
};
