#!/usr/bin/env bash
# Rebuild the derived media this composition needs.
#
#   ./scripts/build-assets.sh <clip1-source.mp4> <clip2-source.mp4>
#
# clip1/clip2 are conformed to a single 30fps / -16 LUFS timebase so the two
# takes cut together without a frame-rate or loudness jump at the join.
set -euo pipefail

SRC1=${1:?usage: build-assets.sh <clip1-source.mp4> <clip2-source.mp4>}
SRC2=${2:?usage: build-assets.sh <clip1-source.mp4> <clip2-source.mp4>}
cd "$(dirname "$0")/.."

measure() {
  ffmpeg -hide_banner -i "$1" -af loudnorm=I=-16:TP=-1.5:LRA=11:print_format=json -f null - 2>&1 |
    sed -n '/^{/,/^}/p'
}

# The source takes are HLG HDR (color_transfer=arib-std-b67, bt2020, 10-bit).
# Re-tagging them to SDR is NOT enough: ffmpeg carries the HDR tags through a
# plain transcode, which leaves an 8-bit file still announcing itself as HLG,
# and every downstream consumer then takes the HDR path. These must be
# tone-mapped HLG -> BT.709 from the 10-bit originals.
TONEMAP="zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p"

conform() {
  local src=$1 out=$2 mi=$3 mtp=$4 mlra=$5 mthr=$6 off=$7
  ffmpeg -v warning -stats -i "$src" \
    -vf "fps=30,scale=1080:1920:flags=lanczos,setsar=1,$TONEMAP" \
    -af "loudnorm=I=-16:TP=-1.5:LRA=11:measured_I=$mi:measured_TP=$mtp:measured_LRA=$mlra:measured_thresh=$mthr:offset=$off:linear=true,aresample=48000" \
    -c:v libx264 -preset medium -crf 17 -pix_fmt yuv420p \
    -colorspace bt709 -color_primaries bt709 -color_trc bt709 -color_range tv \
    -c:a aac -b:a 192k -ar 48000 -ac 2 -movflags +faststart "$out" -y
}

echo "== measuring loudness (pass 1) =="
echo "clip1:"; measure "$SRC1"
echo "clip2:"; measure "$SRC2"
echo
echo "Copy each measurement into the conform calls below, then re-run with them."
echo "The values committed with this project were:"
echo "  clip1  I=-20.47 TP=-2.08 LRA=3.60 thresh=-30.62 offset=0.53"
echo "  clip2  I=-17.90 TP=-0.09 LRA=5.00 thresh=-28.19 offset=-0.61"
echo

conform "$SRC1" assets/clip1.mp4 -20.47 -2.08 3.60 -30.62 0.53
conform "$SRC2" assets/clip2.mp4 -17.90 -0.09 5.00 -28.19 -0.61

echo "== synthesising sfx + ambient bed =="
( cd assets && python3 ../scripts/make-sfx.py )

echo "== done =="
ffprobe -v error -show_entries format=duration -show_entries stream=codec_name,width,height,r_frame_rate -of csv=p=0 assets/clip1.mp4
ffprobe -v error -show_entries format=duration -show_entries stream=codec_name,width,height,r_frame_rate -of csv=p=0 assets/clip2.mp4

echo "== colour sanity: both must read bt709, never bt2020/arib-std-b67 =="
for f in assets/clip1.mp4 assets/clip2.mp4; do
  echo "--- $f"
  ffprobe -v error -select_streams v:0 \
    -show_entries stream=pix_fmt,color_space,color_transfer,color_primaries \
    -of default=noprint_wrappers=1 "$f"
done
