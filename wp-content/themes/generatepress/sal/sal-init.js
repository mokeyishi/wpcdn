const animation = sal();

const switchAnimations = () => {
  if (window.innerWidth < 768) {
    animation.reset();
    animation.disable();
  } else {
    animation.reset();
    animation.enable();
  }
};

switchAnimations();
window.addEventListener('resize', switchAnimations);