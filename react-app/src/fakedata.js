export const lineChartData = {
    labels: [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
        
    ],
    datasets:[
        {
            label: "Steps",
            data: [300, 500, 800, 900, 850, 450, 510],
            borderColor: "rgb(75, 192, 192)",
            backgroundColor: "rgba(75, 192, 192, 0.2)",
            fill: true,
            tension: 0.3,
        },
    ],
};

export const pieChartData = {
    labels: ["Completed", "Missing"],
    datasets:[
        {
            label: "Progress",
            data: ["65", "35"],
            backgroundColor:[
                "rgba(0, 14, 118, 1)",
                "rgba(93, 2, 22, 1)"
            ],
            hoverOffset: 4,
        }
    ]
}