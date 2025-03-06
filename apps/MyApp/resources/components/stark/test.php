<div data-stark-app class="container">
    <script type="module">
        import data from "./assets/js/data.js";
        import Stark from "./assets/js/stark/stark.js";

        Stark.initData(document.querySelector('[data-stark-app]'), data);
    </script>

    <div class="form-wrapper">
        <h1 class="font-black">Testing Forge Stark</h1>
        <div class="form-group">
            <input type="text" st-bind:value="message">
            <p>{{ message }}</p>
        </div>

        <div class="form-group">
            <input type="text" st-on:input.debounce:500="handleInput">
            <p>{{ message }}</p>
        </div>

        <div class="form-group">
            <input type="text" st-model="message">
            <p>{{ message }}</p>
        </div>

        <div class="form-group flex justify-center items-center">
            <div>
                <button st-on:click="increment">Increment</button>
            </div>
            <p class="p-4 uppercase text-5xl font-bold">{{ count }}</p>
            <div>
                <button st-on:click="decrement">Decrement</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div>
            <button st-on:click="toggleHidden">Toggle</button>

            <div st-if="isHidden">
                <h3>This content is hidden by default in Stark.js</h3>
            </div>
        </div>
    </div>
</div>
