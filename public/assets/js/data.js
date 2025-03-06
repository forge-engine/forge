export default {
    count: 0,
    message: "Hello Stark!",
    increment: function () {
        this.count++;
    },
    decrement: function () {
        this.count--;
    },
    trimmedInput: "",
    numberInput: 0,
    debouncedInput: "",

    isHidden: false,
    toggleHidden: function () {
        this.isHidden = !this.isHidden;
    },
    handleInput(event) {
        console.log('Input:', event.target.value);
        this.message = event.target.value;
    },
};