        // 鱼的类定义，用于表示单条鱼的属性和行为
        function Fish() {
            this.x = 0; // 鱼的初始x坐标
            this.y = 0; // 鱼的初始y坐标
            this.speedX = 0; // x方向速度
            this.speedY = 0; // y方向速度
            this.accelerationX = 0; // x方向加速度
            this.accelerationY = 0; // y方向加速度
            this.angle = 0; // 鱼的角度，用于确定方向
            this.size = 20; // 鱼的大小，可调整
            this.init = function () {
                // 初始化鱼的位置等属性，随机分布在画布内
                this.x = Math.random() * window.innerWidth;
                this.y = Math.random() * window.innerHeight;
                this.speedX = (Math.random() - 0.5) * 2;
                this.speedY = (Math.random() - 0.5) * 2;
            };
            this.update = function () {
                // 更新鱼的速度和位置
                this.speedX += this.accelerationX;
                this.speedY += this.accelerationY;
                this.x += this.speedX;
                this.y += this.speedY;
                // 边界检测，鱼游出画布范围则重新初始化位置
                if (this.x < -this.size || this.x > window.innerWidth + this.size ||
                    this.y < -this.size || this.y > window.innerHeight + this.size) {
                    this.init();
                }
            };
            this.draw = function (ctx) {
                // 绘制鱼的形状，这里简单用贝塞尔曲线绘制一个类似鱼的形状，可进一步美化
                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.bezierCurveTo(this.x + this.size / 2, this.y - this.size / 2,
                    this.x + this.size, this.y + this.size / 2, this.x + this.size / 2, this.y + this.size);
                ctx.closePath();
                ctx.fillStyle ='steelblue'; // 设置鱼的颜色，可调整
                ctx.fill();
            };
        }

        // 水面点的类定义，用于表示水面波动的各个点的属性和行为
        function WaterPoint(x, y) {
            this.x = x; // 点的x坐标
            this.y = y; // 点的y坐标
            this.targetY = y; // 目标y坐标，用于波动效果
            this.speed = 0; // 点的波动速度
            this.update = function () {
                // 更新点的位置，向目标位置移动
                this.speed += (this.targetY - this.y) * 0.05;
                this.y += this.speed;
            };
            this.draw = function (ctx) {
                ctx.beginPath();
                ctx.arc(this.x, this.y, 2, 0, 2 * Math.PI);
                ctx.closePath();
                ctx.fillStyle = 'lightblue'; // 设置水的颜色，可调整
                ctx.fill();
            };
        }

        // 渲染器类，用于整体管理动画的渲染，包括鱼和水的绘制等
        function Renderer() {
            this.canvas = document.getElementById('fish-canvas');
            this.ctx = this.canvas.getContext('canvas');
            this.width = window.innerWidth;
            this.height = window.innerHeight;
            this.fishes = [];
            this.waterPoints = [];
            this.init = function () {
                // 初始化画布尺寸、鱼和水的点
                this.canvas.width = this.width;
                this.canvas.height = this.height;
                for (let i = 0; i < 10; i++) { // 创建一定数量的鱼，可调整数量
                    let fish = new Fish();
                    fish.init();
                    this.fishes.push(fish);
                }
                const pointCount = 50; // 水的点的数量，可调整
                const pointGap = this.width / pointCount;
                for (let x = 0; x < this.width; x += pointGap) {
                    let point = new WaterPoint(x, this.height / 2);
                    this.waterPoints.push(point);
                }
            };
            this.update = function () {
                // 更新鱼和水的点的状态
                for (let fish of this.fishes) {
                    fish.update();
                }
                for (let point of this.waterPoints) {
                    point.update();
                }
            };
            this.draw = function () {
                // 绘制鱼和水
                this.ctx.clearRect(0, 0, this.width, this.height);
                for (let fish of this.fishes) {
                    fish.draw(this.ctx);
                }
                this.ctx.beginPath();
                this.ctx.moveTo(0, this.height / 2);
                for (let point of this.waterPoints) {
                    point.draw(this.ctx);
                    this.ctx.lineTo(point.x, point.y);
                }
                this.ctx.lineTo(this.width, this.height / 2);
                this.ctx.closePath();
                this.ctx.fillStyle = 'lightblue';
                this.ctx.fill();
            };
            this.animate = function () {
                // 动画循环，不断更新和绘制
                requestAnimationFrame(() => this.animate());
                this.update();
                this.draw();
            };
        }

        const renderer = new Renderer();
        renderer.init();
        renderer.animate();
