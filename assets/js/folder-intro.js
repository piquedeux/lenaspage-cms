import * as THREE from 'three';
import { FontLoader } from 'three/addons/loaders/FontLoader.js';
import { TextGeometry } from 'three/addons/geometries/TextGeometry.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

// Folder Intro with Three.js
(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('folder-intro');
    const canvasWrap = document.getElementById('folder-canvas');
    
    if (!overlay || !canvasWrap) return;
    if (sessionStorage.getItem('folderIntroShown')) return;

    // Show overlay and lock body scroll
    overlay.style.display = 'flex';
    document.body.classList.add('folder-active');

    function finishIntro() {
      overlay.style.display = 'none';
      document.body.classList.remove('folder-active');
      sessionStorage.setItem('folderIntroShown', '1');
    }

    function initScene() {
      const scene = new THREE.Scene();
      const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
      const width = window.innerWidth;
      const height = window.innerHeight;
      
      renderer.setSize(width, height);
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      renderer.setClearColor(0x000000, 0);
      canvasWrap.appendChild(renderer.domElement);

      const camera = new THREE.PerspectiveCamera(35, width / height, 0.1, 100);
      const isMobile = width < 768;
      camera.position.set(0, 0.5, isMobile ? 16 : 12);

      // Mouse tracking for folder movement
      let mouseX = 0;
      let mouseY = 0;
      let targetFolderX = 0;
      let targetFolderY = 0;

      const onMouseMove = (e) => {
        mouseX = (e.clientX / width) * 2 - 1;
        mouseY = -(e.clientY / height) * 2 + 1;
        targetFolderX = mouseX * 1.5;
        targetFolderY = mouseY * 1.0;
      };
      window.addEventListener('mousemove', onMouseMove);

      // OrbitControls
      const controls = new OrbitControls(camera, renderer.domElement);
      controls.enableDamping = true;
      controls.dampingFactor = 0.05;
      controls.enableZoom = false;
      controls.enablePan = false;
      controls.minPolarAngle = Math.PI / 4;
      controls.maxPolarAngle = Math.PI / 1.5;

      // Lighting
      scene.add(new THREE.AmbientLight(0xffffff, 0.9));
      const dir = new THREE.DirectionalLight(0xffffff, 0.7);
      dir.position.set(3, 4, 5);
      scene.add(dir);

      const folderGroup = new THREE.Group();
      scene.add(folderGroup);

      // Grain texture
      const grainCanvas = document.createElement('canvas');
      grainCanvas.width = 512;
      grainCanvas.height = 512;
      const ctx = grainCanvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, 512, 512);
      const imageData = ctx.getImageData(0, 0, 512, 512);
      for (let i = 0; i < imageData.data.length; i += 4) {
        const noise = Math.random() * 20 - 10;
        imageData.data[i] += noise;
        imageData.data[i + 1] += noise;
        imageData.data[i + 2] += noise;
      }
      ctx.putImageData(imageData, 0, 0);
      const grainTexture = new THREE.CanvasTexture(grainCanvas);
      grainTexture.wrapS = grainTexture.wrapT = THREE.RepeatWrapping;
      grainTexture.repeat.set(2, 2);

      // Normal map for surface imperfections
      const normalCanvas = document.createElement('canvas');
      normalCanvas.width = 256;
      normalCanvas.height = 256;
      const nCtx = normalCanvas.getContext('2d');
      nCtx.fillStyle = '#8080ff';
      nCtx.fillRect(0, 0, 256, 256);
      const nImageData = nCtx.getImageData(0, 0, 256, 256);
      for (let i = 0; i < nImageData.data.length; i += 4) {
        const bump = Math.random() * 60 - 30;
        nImageData.data[i] = Math.max(0, Math.min(255, 128 + bump));
        nImageData.data[i + 1] = Math.max(0, Math.min(255, 128 + bump));
        nImageData.data[i + 2] = 255;
        nImageData.data[i + 3] = 255;
      }
      nCtx.putImageData(nImageData, 0, 0);
      const normalTexture = new THREE.CanvasTexture(normalCanvas);
      normalTexture.wrapS = normalTexture.wrapT = THREE.RepeatWrapping;
      normalTexture.repeat.set(2, 2);

      // Materials - white with grain
      const baseMat = new THREE.MeshStandardMaterial({ 
        color: 0xffffff,
        map: grainTexture,
        normalMap: normalTexture,
        roughness: 0.75, 
        metalness: 0.01 
      });
      const coverMat = new THREE.MeshStandardMaterial({ 
        color: 0xffffff,
        map: grainTexture,
        normalMap: normalTexture,
        roughness: 0.7, 
        metalness: 0.02 
      });

      // Base with slight organic randomness
      const baseGeo = new THREE.BoxGeometry(4.2, 5.8, 0.2, 8, 8, 1);
      const positions = baseGeo.attributes.position;
      for (let i = 0; i < positions.count; i++) {
        positions.array[i * 3] += (Math.random() - 0.5) * 0.02;
        positions.array[i * 3 + 1] += (Math.random() - 0.5) * 0.02;
        positions.array[i * 3 + 2] += (Math.random() - 0.5) * 0.01;
      }
      positions.needsUpdate = true;
      baseGeo.computeVertexNormals();

      const base = new THREE.Mesh(baseGeo, baseMat);
      base.position.set(0, 0, -0.08);
      base.geometry.computeBoundingBox();
      folderGroup.add(base);

      // Cover with randomness
      const coverGeo = new THREE.BoxGeometry(4.2, 5.8, 0.1, 8, 8, 1);
      const coverPositions = coverGeo.attributes.position;
      for (let i = 0; i < coverPositions.count; i++) {
        coverPositions.array[i * 3] += (Math.random() - 0.5) * 0.02;
        coverPositions.array[i * 3 + 1] += (Math.random() - 0.5) * 0.02;
        coverPositions.array[i * 3 + 2] += (Math.random() - 0.5) * 0.01;
      }
      coverPositions.needsUpdate = true;
      coverGeo.computeVertexNormals();

      // Hinge at the left edge so it opens to the right (horizontally)
      const coverHinge = new THREE.Group();
      coverHinge.position.set(-2.1, 0, 0.07); // pivot at the left edge of the base
      const cover = new THREE.Mesh(coverGeo, coverMat);
      cover.position.set(2.1, 0, 0); // move cover right so left edge sits on hinge
      coverHinge.add(cover);
      folderGroup.add(coverHinge);

      // Add folder pockets/tabs on the right side (visible from front)
      const pocketMat = new THREE.MeshStandardMaterial({ 
        color: 0xf5f5f5,
        map: grainTexture,
        roughness: 0.75, 
        metalness: 0.01,
        side: THREE.DoubleSide
      });

      // Pocket dividers - vertical tabs on the right
      const pocket1Geo = new THREE.BoxGeometry(0.08, 1.2, 0.15);
      const pocket1 = new THREE.Mesh(pocket1Geo, pocketMat);
      pocket1.position.set(1.85, 1.5, 0.1);
      base.add(pocket1);

      const pocket2 = new THREE.Mesh(pocket1Geo, pocketMat);
      pocket2.position.set(1.85, 0, 0.1);
      base.add(pocket2);

      const pocket3 = new THREE.Mesh(pocket1Geo, pocketMat);
      pocket3.position.set(1.85, -1.5, 0.1);
      base.add(pocket3);

      // Horizontal separator lines
      const separatorGeo = new THREE.BoxGeometry(0.5, 0.05, 0.1);
      const separator1 = new THREE.Mesh(separatorGeo, pocketMat);
      separator1.position.set(1.6, 0.75, 0.1);
      base.add(separator1);

      const separator2 = new THREE.Mesh(separatorGeo, pocketMat);
      separator2.position.set(1.6, -0.75, 0.1);
      base.add(separator2);

      // String loops (dark) - attach to cover so they flip with it
      const cordGeo = new THREE.TorusGeometry(0.08, 0.025, 12, 32);
      const cordMat = new THREE.MeshStandardMaterial({ 
        color: 0x222222, 
        roughness: 0.5, 
        metalness: 0.1 
      });
      const loop1 = new THREE.Mesh(cordGeo, cordMat);
      loop1.position.set(-1.4, -0.6, 0.18);
      cover.add(loop1);
      const loop2 = loop1.clone();
      loop2.position.set(0.8, -0.6, 0.18);
      cover.add(loop2);

      folderGroup.rotation.x = -0.1;
      folderGroup.rotation.y = 0.1;

      // 3D Text setup
      const loader = new FontLoader();
      const textObjects = [];
      const clickableLinks = [];

      function addText(str, font, mat, x, y, z, size, isClickable = false) {
        const geo = new TextGeometry(str, {
          font: font,
          size: size,
          height: 0.02,
          curveSegments: 8,
          bevelEnabled: false
        });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.set(x, y, z);
        cover.add(mesh);
        if (isClickable) mesh.userData.clickable = true;
        textObjects.push(mesh);
        return mesh;
      }

      loader.load(
        'https://cdn.jsdelivr.net/npm/three@0.160.0/examples/fonts/helvetiker_regular.typeface.json',
        function(font) {
          const textMat = new THREE.MeshStandardMaterial({ 
            color: 0x111111, 
            roughness: 0.7 
          });

          // Top left: home link - clickable
          const homeText = addText('home', font, textMat, -1.6, 2.3, 0.25, 0.16, true);
          if (homeText) clickableLinks.push({ mesh: homeText, url: window.siteUrl || '/' });

          // Top left: info link - clickable
          const infoText = addText('info', font, textMat, -1.6, 2.0, 0.25, 0.16, true);
          if (infoText) clickableLinks.push({ mesh: infoText, url: (window.siteUrl || '') + '/about' });

          // Top middle: portfolio and designer - clickable
          const portfolioText = addText('portfolio', font, textMat, -0.2, 2.3, 0.25, 0.14, true);
          if (portfolioText) clickableLinks.push({ mesh: portfolioText, url: (window.siteUrl || '') + '/projects' });

          const designerText = addText('designer', font, textMat, -0.2, 2.05, 0.25, 0.14, true);
          if (designerText) clickableLinks.push({ mesh: designerText, url: (window.siteUrl || '') + '/about' });

          // Center large: "lena rickenstorf" text (no longer the opener)
          addText('lena rickenstorf', font, textMat, -1.5, -0.2, 0.35, 0.3, false);
        }
      );

      // --- Opening link directly on the folder cover (transparent plane) ---
      const openerPlaneGeo = new THREE.PlaneGeometry(4.0, 5.2);
      const openerPlaneMat = new THREE.MeshBasicMaterial({
        color: 0x000000,
        transparent: true,
        opacity: 0,   // fully transparent
        depthWrite: false
      });
      const openerPlane = new THREE.Mesh(openerPlaneGeo, openerPlaneMat);
      openerPlane.position.set(0, 0, 0.051); // slightly above cover surface
      openerPlane.userData.clickable = true;
      openerPlane.userData.opensFolder = true;
      cover.add(openerPlane);

      // Animation state
      let isOpening = false;
      let hasOpened = false;
      let fadeOutStarted = false;
      let zoomingIn = false;
      const raycaster = new THREE.Raycaster();
      const mouse = new THREE.Vector2();

      function animate() {
        requestAnimationFrame(animate);
        controls.update();
        
        // Smooth folder movement based on mouse position
        if (!isOpening) {
          folderGroup.position.x += (targetFolderX - folderGroup.position.x) * 0.05;
          folderGroup.position.y += (targetFolderY - folderGroup.position.y) * 0.05;
        }

        renderer.render(scene, camera);
      }
      animate();

      function handleOpenSequence() {
        if (isOpening || hasOpened) return;
        isOpening = true;
        zoomingIn = true;
        controls.enabled = false;
        renderer.domElement.style.cursor = 'default';

        // Start fading out overlay backdrop immediately so website becomes visible
        overlay.style.transition = 'background-color 1.5s ease-out';
        overlay.style.backgroundColor = 'transparent';

        // Animate folder opening (flip cover to the right horizontally)
        const startRotation = coverHinge.rotation.y;
        const targetRotation = Math.PI * 0.95; // flip completely open to the right
        const startCameraZ = camera.position.z;
        const targetCameraZ = 3; // zoom in close
        const startCameraX = camera.position.x;
        const targetCameraX = 1.5; // shift to the right to see content
        const duration = 1800;
        const startTime = Date.now();

        function animateFlip() {
          const elapsed = Date.now() - startTime;
          const progress = Math.min(elapsed / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic

          // Flip the cover horizontally
          coverHinge.rotation.y = startRotation + (targetRotation - startRotation) * eased;
          
          // Delay zoom: only start zooming after 40% of flip animation
          const zoomDelay = 0.4;
          if (progress > zoomDelay) {
            const zoomProgress = (progress - zoomDelay) / (1 - zoomDelay);
            const zoomEased = 1 - Math.pow(1 - zoomProgress, 3);
            
            camera.position.z = startCameraZ + (targetCameraZ - startCameraZ) * zoomEased;
            camera.position.x = startCameraX + (targetCameraX - startCameraX) * zoomEased;
          }

          if (progress < 1) {
            requestAnimationFrame(animateFlip);
          } else {
            // After flip completes, fade out canvas and overlay completely
            overlay.style.transition = 'opacity 0.8s ease-out';
            overlay.style.opacity = '0';
            
            setTimeout(() => {
              hasOpened = true;
              finishIntro();
            }, 800);
          }
        }

        animateFlip();
      }

      function handleClick(event) {
        if (isOpening) return;

        // Prevent default to avoid page zoom on mobile
        event.preventDefault();

        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        raycaster.setFromCamera(mouse, camera);

        // Test both text and opener plane
        const interactiveObjects = textObjects.concat([openerPlane]);
        const intersects = raycaster.intersectObjects(interactiveObjects, false);

        if (intersects.length > 0) {
          const obj = intersects[0].object;

          // Folder opener plane
          if (obj.userData.opensFolder) {
            handleOpenSequence();
            return;
          }

          // Text links
          const link = clickableLinks.find(l => l.mesh === obj);
          if (link && link.url) {
            window.location.href = link.url;
          }
        }
      }

      function handleMove(event) {
        if (isOpening) return;

        const rect = renderer.domElement.getBoundingClientRect();
        mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        raycaster.setFromCamera(mouse, camera);
        const interactiveObjects = textObjects.concat([openerPlane]);
        const intersects = raycaster.intersectObjects(interactiveObjects, false);

        if (intersects.length > 0 && intersects[0].object.userData.clickable) {
          renderer.domElement.style.cursor = 'pointer';
        } else {
          renderer.domElement.style.cursor = 'grab';
        }
      }

      renderer.domElement.addEventListener('click', handleClick);
      renderer.domElement.addEventListener('mousemove', handleMove);

      // Resize
      window.addEventListener('resize', () => {
        const w = window.innerWidth;
        const h = window.innerHeight;
        renderer.setSize(w, h);
        camera.aspect = w / h;
        camera.updateProjectionMatrix;
        const mobile = w < 768;
        if (!zoomingIn) {
          camera.position.z = mobile ? 16 : 12;
        }
      });
    }

    initScene();
  });
})();
