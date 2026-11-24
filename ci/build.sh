#!/usr/bin/env sh
#
# Builds the plugin from master and, when SVN_DIR is set, releases it to the
# WordPress.org SVN checkout: copies it to trunk/ and tags/<version> and asks
# before committing.
#
#   ci/build/cf7-to-zapier/                the plugin files
#   ci/build/cf7-to-zapier-<version>.zip   installable from the WordPress admin
#
# SVN_DIR is the local checkout of https://plugins.svn.wordpress.org/cf7-to-zapier.
# Set it in the environment or in ci/.env (see ci/.env.example).
#
# Usage: ./ci/build.sh

set -e

cd "$(dirname "$0")/.."

SLUG=cf7-to-zapier
REF=master
BUILD_DIR=ci/build

# What WordPress.org gets. Repository files (README.md, CLAUDE.md, CONTRIBUTING.md, ci/) stay out.
FILES="cf7-to-zapier.php index.php LICENSE.txt readme.txt includes languages modules"

# Asks a yes/no question. Anything but "y" means no.
confirm() {
    printf '%s [y/N] ' "$1"
    read -r answer || answer=''
    case "$answer" in
        [yY]*) return 0 ;;
        *) return 1 ;;
    esac
}

# SVN_DIR from the environment wins over ci/.env.
ENV_SVN_DIR=$SVN_DIR
if [ -f ci/.env ]; then
    . ./ci/.env
fi
SVN_DIR=${ENV_SVN_DIR:-$SVN_DIR}

# Releases come from master as it is on GitHub, never from a local-only commit.
git fetch -q origin master
if [ "$(git rev-parse master)" != "$(git rev-parse origin/master)" ]; then
    echo "Local master and origin/master differ. Pull or push before building."
    exit 1
fi

if [ "$(git rev-parse --abbrev-ref HEAD)" = "master" ] && ! git diff --quiet HEAD -- $FILES; then
    echo "Warning: uncommitted changes in plugin files are not included."
fi

# The version lives in four places and they must agree (see CLAUDE.md).
VERSION=$(git show "$REF:$SLUG.php" | sed -n 's/^ \* Version: *//p')
CONSTANT=$(git show "$REF:$SLUG.php" | sed -n "s/.*define( 'CFTZ_VERSION', '\([^']*\)' );.*/\1/p")
STABLE_TAG=$(git show "$REF:readme.txt" | sed -n 's/^Stable tag: *//p')

if [ -z "$VERSION" ] || [ "$VERSION" != "$CONSTANT" ] || [ "$VERSION" != "$STABLE_TAG" ]; then
    echo "Version mismatch in $REF:"
    echo "  Version header: $VERSION"
    echo "  CFTZ_VERSION:   $CONSTANT"
    echo "  Stable tag:     $STABLE_TAG"
    exit 1
fi

if ! git show "$REF:readme.txt" | grep -q "^= $VERSION =$"; then
    echo "readme.txt in $REF has no changelog entry for $VERSION."
    exit 1
fi

rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

git archive --format=tar --prefix="$SLUG/" "$REF" $FILES | tar -x -C "$BUILD_DIR"
git archive --format=zip --prefix="$SLUG/" -o "$BUILD_DIR/$SLUG-$VERSION.zip" "$REF" $FILES

BUILD_PATH="$(pwd)/$BUILD_DIR/$SLUG"

echo "Built $SLUG $VERSION from $REF:"
echo "  $BUILD_DIR/$SLUG/"
echo "  $BUILD_DIR/$SLUG-$VERSION.zip"

if [ -z "$SVN_DIR" ]; then
    echo "SVN_DIR is not set, so nothing was copied to SVN."
    exit 0
fi

# ---------------------------------------------------------------------------
# Release to SVN
# ---------------------------------------------------------------------------

if ! command -v svn > /dev/null; then
    echo "svn is not installed."
    exit 1
fi

if [ ! -d "$SVN_DIR/trunk" ] || [ ! -d "$SVN_DIR/tags" ] || ! svn info "$SVN_DIR" > /dev/null 2>&1; then
    echo "$SVN_DIR is not an SVN checkout with trunk/ and tags/."
    exit 1
fi

cd "$SVN_DIR"

echo
echo "Updating the SVN checkout..."
svn update -q

# Anything already pending would go in the same commit.
PENDING=$(svn status)
if [ -n "$PENDING" ]; then
    echo "The SVN checkout already has uncommitted changes:"
    echo "$PENDING"
    confirm "They will be committed together with $VERSION. Continue?" || exit 1
fi

# Sites only update to a higher version.
TRUNK_VERSION=$(sed -n 's/^ \* Version: *//p' "trunk/$SLUG.php" 2> /dev/null || true)
if [ -n "$TRUNK_VERSION" ]; then
    HIGHEST=$(printf '%s\n%s\n' "$TRUNK_VERSION" "$VERSION" | sort -V | tail -n 1)
    if [ "$VERSION" = "$TRUNK_VERSION" ] || [ "$HIGHEST" != "$VERSION" ]; then
        echo "trunk has $TRUNK_VERSION and master has $VERSION. Sites only update to a higher version."
        confirm "Continue anyway?" || exit 1
    fi
fi

if [ -e "tags/$VERSION" ]; then
    confirm "tags/$VERSION already exists, so $VERSION was probably released. Overwrite it?" || exit 1
fi

# Replace trunk and the tag with the build.
mkdir -p "tags/$VERSION"
find trunk "tags/$VERSION" -mindepth 1 -delete
cp -R "$BUILD_PATH/." trunk/
cp -R "$BUILD_PATH/." "tags/$VERSION/"

# Files removed from the plugin must be deleted in SVN too, or they stay in the release.
svn status trunk "tags/$VERSION" | sed -n 's/^! *//p' | while IFS= read -r file; do
    svn rm -q "$file"
done

svn add -q * --force

CHANGES=$(svn status)
if [ -z "$CHANGES" ]; then
    echo "SVN already matches the build. Nothing to commit."
    exit 0
fi

echo
echo "$CHANGES"
echo

if confirm "Commit to WordPress.org with: svn ci -m \"v$VERSION\"?"; then
    svn ci -m "v$VERSION"
else
    echo "Nothing was committed. To commit later, run in $SVN_DIR:"
    echo "  svn ci -m \"v$VERSION\""
fi
