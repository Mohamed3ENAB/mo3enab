import wave, struct, math, random

SR = 48000

def write(name, samples, peak=0.9):
    m = max(1e-9, max(abs(s) for s in samples))
    g = peak / m
    with wave.open(name, 'w') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes(b''.join(struct.pack('<h', int(max(-1,min(1,s*g))*32767)) for s in samples))
    print(f"  {name}  {len(samples)/SR:.2f}s")

def lp(sig, fc_fn):
    y=0.0; out=[]
    for i,x in enumerate(sig):
        fc=fc_fn(i/len(sig)); a=1-math.exp(-2*math.pi*fc/SR)
        y+=a*(x-y); out.append(y)
    return out

def hp(sig, fc_fn):
    return [x-y for x,y in zip(sig, lp(sig, fc_fn))]

def noise(n, seed=7):
    r=random.Random(seed); return [r.uniform(-1,1) for _ in range(n)]

def env(n, attack, decay, curve=2.0):
    out=[]
    a=max(1,int(n*attack))
    for i in range(n):
        if i<a: v=(i/a)**0.6
        else:
            t=(i-a)/max(1,(n-a)); v=(1-t)**curve
        out.append(v)
    return out

# ---- 1. whoosh: noise, bandpass sweeping up, bell envelope
n=int(0.50*SR)
s=noise(n,11)
s=lp(s, lambda t: 600+5200*t)          # cutoff rises
s=hp(s, lambda t: 200+1400*t)          # lower edge rises too
e=env(n,0.35,0.65,2.2)
write('whoosh.wav',[x*y for x,y in zip(s,e)],0.75)

# ---- 2. whoosh-down: reverse sweep, for exits / transition
n=int(0.45*SR)
s=noise(n,23)
s=lp(s, lambda t: 5000-4200*t)
s=hp(s, lambda t: 1200-900*t)
e=env(n,0.18,0.82,1.8)
write('whoosh_down.wav',[x*y for x,y in zip(s,e)],0.75)

# ---- 3. tick: short click for stat pops
n=int(0.11*SR)
out=[]
rnd=random.Random(5)
for i in range(n):
    t=i/SR; d=math.exp(-t*85)
    out.append((math.sin(2*math.pi*1450*t)*0.6 + rnd.uniform(-1,1)*0.25)*d)
write('tick.wav',out,0.55)

# ---- 4. ding: clean two-partial bell for the award / price hit
n=int(1.1*SR); out=[]
for i in range(n):
    t=i/SR
    v =math.sin(2*math.pi*1046.5*t)*math.exp(-t*4.5)
    v+=math.sin(2*math.pi*1568.0*t)*0.45*math.exp(-t*6.0)
    v+=math.sin(2*math.pi*2093.0*t)*0.18*math.exp(-t*9.0)
    out.append(v)
write('ding.wav',out,0.62)

# ---- 5. sub hit: low impact under big reveals
n=int(0.75*SR); out=[]; ph=0.0
for i in range(n):
    t=i/SR; f=72*math.exp(-t*3.2)+34
    ph+=2*math.pi*f/SR
    out.append(math.sin(ph)*math.exp(-t*3.6))
write('subhit.wav',out,0.85)

# ---- 6. riser: builds into the CTA
n=int(1.4*SR)
s=noise(n,41)
s=lp(s, lambda t: 500+6500*(t**1.7))
s=hp(s, lambda t: 300+600*t)
out=[]
for i,x in enumerate(s):
    t=i/n
    ph=2*math.pi*(220+680*(t**2))*(i/SR)
    out.append(x*(t**1.5)*0.8 + math.sin(ph)*(t**2.5)*0.35)
write('riser.wav',out,0.7)

# ---- 7. bed: very subtle ambient drone, 46s, sits far under the voice
dur=46.0; n=int(dur*SR); out=[]
parts=[(55.0,1.0),(82.5,0.55),(110.0,0.38),(164.8,0.16),(220.0,0.09)]
for i in range(n):
    t=i/SR
    v=0.0
    for f,a in parts:
        lfo=1.0+0.18*math.sin(2*math.pi*(0.035+f*0.0002)*t)
        v+=math.sin(2*math.pi*f*t)*a*lfo
    v*= 0.5+0.5*min(1.0,t/3.0)                 # fade in
    if t>dur-3.5: v*= max(0.0,(dur-t)/3.5)     # fade out
    out.append(v)
out=lp(out, lambda x: 320.0)
write('bed.wav',out,0.5)
