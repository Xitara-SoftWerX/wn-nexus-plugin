declare module '*.scss';
declare module '*.css';
declare module '*.png' {
    const url: string;
    export default url;
}
