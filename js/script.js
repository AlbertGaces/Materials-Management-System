/* ==================== START - JQUERY READY ==================== */

$(document).ready(function () {

    function StopConsoleText() {
        console.log("%cJust Don't", "color: red; font-family: sans-serif; font-size: 3em; font-weight: bolder; text-shadow: #000 1px 1px;")
    }
    StopConsoleText();

    $(".password-requirements").PassRequirements();
    
    /* ==================== START - CIRCULAR PROGRESS ==================== */

    function animateElements() {
        $('.progressbar').each(function () {
            var elementPos = $(this).offset().top;
            var topOfWindow = $(window).scrollTop();
            var percent = $(this).find('.circle').attr('data-percent');
            var animate = $(this).data('animate');
            if (elementPos < topOfWindow + $(window).height() - 30 && !animate) {
                var percentage = $(this).find('.circle').attr('data-percent');
                if (percentage > 75) {
                    $(this).find('.circle').circleProgress({
                        value: (percent / 100),
                        size: 300,
                        thickness: 30,
                        fill: {
                            color: '#198754'
                        }
                    })
                }
                else if (percentage > 50) {
                    $(this).find('.circle').circleProgress({
                        value: percent / 100,
                        size: 300,
                        thickness: 30,
                        fill: {
                            color: '#ffc107'
                        }
                    })
                }
                else if (percentage > 25) {
                    $(this).find('.circle').circleProgress({
                        value: percent / 100,
                        size: 300,
                        thickness: 30,
                        fill: {
                            color: '#fd7e14'
                        }
                    })
                }
                else {
                    $(this).find('.circle').circleProgress({
                        value: percent / 100,
                        size: 300,
                        thickness: 30,
                        fill: {
                            color: '#dc3545'
                        }
                    })
                }
                $(this).data('animate', true);
                $(this).find('.circle').circleProgress().on('circle-animation-progress', function (event, progress, stepValue) {
                    $(this).find('strong').text((stepValue * 100).toFixed(1) + "%");
                }).stop();
            }
        });
    }
    animateElements();
    $(window).scroll(animateElements);

    /* ==================== END - CIRCULAR PROGRESS ==================== */

    /* ==================== START - MATERIAL AJAX ==================== */

    $('#selectMaterialNameContainer').hide();
    $("#selectMaterialType").on("change",function(){
        var typeID = $(this).val();
        if (typeID) {
            $.ajax({
                url :"include/action.php",
                type:"POST",
                cache:false,
                data:{typeID:typeID},
                success:function(data){
                    $("#selectMaterialName").html(data);
                }
            });
        }
        else{
            $('#selectMaterialName').html('<option value="">Choose material type first</option>');
        }

        if(!$('#selectMaterialType').val()){
            $('#selectMaterialNameContainer').hide();
        }
        else {
            $('#selectMaterialNameContainer').show();
        }
    });

    /* ==================== END - MATERIAL AJAX ==================== */

    /* ==================== START - INITIALIZATION OF DATATABLES ==================== */

    $('.datatable-desc-1').DataTable({
        "scrollX": false,
        "ordering": true,
        columnDefs: [{
            orderable: false,
            targets: "no-sort"
        }],
        "order": [[ 0, "desc" ]]
    });

    $('.datatable-desc-2').DataTable({
        "scrollX": false,
        "ordering": true,
        columnDefs: [{
            orderable: false,
            targets: "no-sort"
        }],
        "order": [[ 1, "desc" ]]
    });

    
    $('.datatable-asc-1').DataTable({
        "scrollX": false,
        "ordering": true,
        columnDefs: [{
            orderable: false,
            targets: "no-sort"
        }]
    });
    
    $('.datatable-asc-2').DataTable({
        "scrollX": false,
        "ordering": true,
        columnDefs: [{
            orderable: false,
            targets: "no-sort"
        }],
        "order": [[ 1, "asc" ]]
    });

    $('.datatable-asc-2-paging-off').DataTable({
        "scrollX": false,
        "paging": false,
        "ordering": true,
        columnDefs: [{
            orderable: false,
            targets: "no-sort"
        }],
        "order": [[ 1, "asc" ]]
    });

    $('.datatable-no-sort').DataTable({
        "ordering": false,
    });

    $('.datatable-no-all').DataTable({
        "ordering": false,
        "paging": false,
        "searching": false,
        "info": false,
    });

    $("a[data-bs-toggle='tab']").on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable()
           .columns.adjust()
           .responsive.recalc();
    });

    /* ==================== END - INITIALIZATION OF DATATABLES ==================== */

    /* ==================== START - INITIALIZATION OF BOOTSTRAP TOOLTIP ==================== */

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    /* ==================== END - INITIALIZATION OF BOOTSTRAP TOOLTIP ==================== */

    /* ==================== START - TOGGLE TOOLTIPS WHEN SIDEBAR IS TOGGLED ==================== */

    let arrow = document.querySelectorAll(".arrow");
    for (var i = 0; i < arrow.length; i++) {
        arrow[i].addEventListener("click", (e) => {
            let arrowParent = e.target.parentElement.parentElement;
            arrowParent.classList.toggle("showMenu");
        });
    }
    let sidebar = document.querySelector(".sidebar");
    let content = document.querySelector(".content")
    let sidebarBtn = document.querySelector(".bx-menu");
    sidebarBtn.addEventListener("click", () => {
        sidebar.classList.toggle("close");
        var elementLink = document.querySelectorAll(".tooltips-toggle");
        if (sidebar.classList.contains("close")) {
            elementLink[0].setAttribute('data-bs-original-title', 'Dashboard');
            elementLink[1].setAttribute('data-bs-original-title', 'Materials');
            elementLink[2].setAttribute('data-bs-original-title', 'Products');
            elementLink[3].setAttribute('data-bs-original-title', 'Projects');
            elementLink[4].setAttribute('data-bs-original-title', 'Scan');
            elementLink[5].setAttribute('data-bs-original-title', 'History');
            elementLink[6].setAttribute('data-bs-original-title', 'Defectives');
            elementLink[7].setAttribute('data-bs-original-title', 'Archives');
            elementLink[8].setAttribute('data-bs-original-title', 'Settings');
        }
        else {
            elementLink[0].setAttribute('data-bs-original-title', '');
            elementLink[1].setAttribute('data-bs-original-title', '');
            elementLink[2].setAttribute('data-bs-original-title', '');
            elementLink[3].setAttribute('data-bs-original-title', '');
            elementLink[4].setAttribute('data-bs-original-title', '');
            elementLink[5].setAttribute('data-bs-original-title', '');
            elementLink[6].setAttribute('data-bs-original-title', '');
            elementLink[7].setAttribute('data-bs-original-title', '');
            elementLink[8].setAttribute('data-bs-original-title', '');
        }
    });

    /* ==================== END - TOGGLE TOOLTIPS WHEN SIDEBAR IS TOGGLED ==================== */

    /* ==================== START - CHANGE/SETTING PROFILE PICTURE ==================== */

    $('.profile-photo-overlay').click(function(){
        $('.profile-photo-upload').click();
        $(".profile-photo-upload").change(function(){
            $('#btnUploadProfilePhoto').click();
        });
    });

    var nameCount = $('.profile-name-content').text().length;
    if (nameCount >= 13) {
        $('.profile-name-content').text($('.profile-name-content').text().slice(0, 12)+'...');
    }



    

    /* ==================== END - CHANGE/SETTING PROFILE PICTURE ==================== */

    /* ==================== START - RESTRICT PAST DATES ==================== */

    $(function(){
        var dtToday = new Date();
        var month = dtToday.getMonth() + 1;
        var day = dtToday.getDate(); 
        var year = dtToday.getFullYear();
        if(month < 10) {
            month = '0' + month.toString();
        }   
        if(day < 10) {
            day = '0' + day.toString();
        }
        
        var minDate = year + '-' + month + '-' + day;
        
        $('.limitedDate').attr('min', minDate);
    });

    /* ==================== END - RESTRICT PAST DATES ==================== */

    /* ==================== START - NEXT AND PREVIOUS BUTTONS IN CREATE ACCOUNT ==================== */

    $("#btnJumpAdvanced").click(function() {
        $("#btnPersonalInfoNav").removeClass("active");  
        $("#createAccountInformation").removeClass("active");  

        $("#btnAdvancedNav").addClass("active");  
        $("#createAccountAdvanced").addClass("active");  
    });

    $("#btnJumpPersonalInfo").click(function() {
        $("#btnAdvancedNav").removeClass("active");  
        $("#createAccountAdvanced").removeClass("active");  

        $("#btnPersonalInfoNav").addClass("active");  
        $("#createAccountInformation").addClass("active");  
    });

    /* ==================== END - NEXT AND PREVIOUS BUTTONS IN CREATE ACCOUNT ==================== */

});



/* ==================== END - JQUERY READY ==================== */



/* ==================== START - JAVASCRIPT READY ==================== */

/* ==================== START - CLONE PROJECT ORDERED PRODUCTS ==================== */

var productCloneCounter = 1
function cloneOrderedProduct() {
    var selectRequiredMaterial = document.getElementById("selectOrderedProduct")
    var cloneSelectRequiredMaterial = selectRequiredMaterial.cloneNode(true)
    var nameSelectRequiredMaterial = selectRequiredMaterial.getAttribute("name") + productCloneCounter
    cloneSelectRequiredMaterial.id = nameSelectRequiredMaterial
    cloneSelectRequiredMaterial.setAttribute("name", nameSelectRequiredMaterial)
    document.getElementById("orderedProductsContainer").appendChild(cloneSelectRequiredMaterial)

    var inputRequiredMaterialQuantity = document.getElementById("orderedProductQuantity")
    var cloneInputRequiredMaterialQuantity = inputRequiredMaterialQuantity.cloneNode(true)
    var nameInputRequiredMaterialQuantity = inputRequiredMaterialQuantity.getAttribute("name") + productCloneCounter
    cloneInputRequiredMaterialQuantity.id = nameInputRequiredMaterialQuantity
    cloneInputRequiredMaterialQuantity.setAttribute("name", nameInputRequiredMaterialQuantity)
    document.getElementById("orderedProductsContainer").appendChild(cloneInputRequiredMaterialQuantity)

    productCloneCounter++
    document.getElementById("inputCloneCounter").value = productCloneCounter;
}

/* ==================== END - CLONE PROJECT ORDERED PRODUCTS ==================== */

/* ==================== START - DELETE CLONE PROJECT ORDERED PRODUCTS ==================== */

function deleteCloneOrderedProduct() {
    if (productCloneCounter == 1) {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Cannot Delete Another',
            text: 'That is the last product row',
            showConfirmButton: false,
            timer: 2000
        });
    }
    else {
        document.getElementById("selectOrderedProduct" + (productCloneCounter-1)).remove();
        document.getElementById("orderedProductQuantity" + (productCloneCounter-1)).remove();
        productCloneCounter--
    }
}

/* ==================== END - DELETE CLONE PROJECT ORDERED PRODUCTS ==================== */

/* ==================== START - CLONE REQUIRED MATERIALS ==================== */

var cloneCounter = 1
function cloneRequiredMaterial() {

    if (cloneCounter >= 10) {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Maximum Materials Limit Reached',
            text: 'You can only add 10 materials at a time',
            showConfirmButton: false,
            timer: 2000
        });
    }
    else {
        var selectRequiredMaterial = document.getElementById("selectRequiredMaterial")
        var cloneSelectRequiredMaterial = selectRequiredMaterial.cloneNode(true)
        var nameSelectRequiredMaterial = selectRequiredMaterial.getAttribute("name") + cloneCounter
        cloneSelectRequiredMaterial.id = nameSelectRequiredMaterial
        cloneSelectRequiredMaterial.setAttribute("name", nameSelectRequiredMaterial)
        document.getElementById("requiredMaterialsContainer").appendChild(cloneSelectRequiredMaterial)

        var inputRequiredMaterialMeasurement = document.getElementById("inputRequiredMaterialMeasurement")
        var cloneInputRequiredMaterialMeasurement = inputRequiredMaterialMeasurement.cloneNode(true)
        var nameInputRequiredMaterialMeasurement = inputRequiredMaterialMeasurement.getAttribute("name") + cloneCounter
        cloneInputRequiredMaterialMeasurement.id = nameInputRequiredMaterialMeasurement
        cloneInputRequiredMaterialMeasurement.setAttribute("name", nameInputRequiredMaterialMeasurement)
        document.getElementById("requiredMaterialsContainer").appendChild(cloneInputRequiredMaterialMeasurement)

        var selectRequiredMaterialUnit = document.getElementById("selectRequiredMaterialUnit")
        var cloneSelectRequiredMaterialUnit = selectRequiredMaterialUnit.cloneNode(true)
        var nameSelectRequiredMaterialUnit = selectRequiredMaterialUnit.getAttribute("name") + cloneCounter
        cloneSelectRequiredMaterialUnit.id = nameSelectRequiredMaterialUnit
        cloneSelectRequiredMaterialUnit.setAttribute("name", nameSelectRequiredMaterialUnit)
        document.getElementById("requiredMaterialsContainer").appendChild(cloneSelectRequiredMaterialUnit)

        cloneCounter++
        document.getElementById("inputCloneCounter").value = cloneCounter;
    }
}

/* ==================== END - CLONE REQUIRED MATERIALS ==================== */

/* ==================== START - DELETE CLONE REQUIRED MATERIALS ==================== */

function deleteRequiredMaterial() {
    if (cloneCounter == 1) {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Cannot Delete Another',
            text: 'That is the last material row',
            showConfirmButton: false,
            timer: 2000
        });
    }
    else {
        document.getElementById("selectRequiredMaterial" + (cloneCounter-1)).remove();
        document.getElementById("inputRequiredMaterialMeasurement" + (cloneCounter-1)).remove();
        document.getElementById("selectRequiredMaterialUnit" + (cloneCounter-1)).remove();
        cloneCounter--;
    }
}

/* ==================== END - DELETE CLONE REQUIRED MATERIALS ==================== */

/* ==================== START - BOOTSTRAP VALIDATION ANIMATION ==================== */

    (function () {
        'use strict'
    
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')
    
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
    
            form.classList.add('was-validated')
            }, false)
        })
    })()

/* ==================== END - BOOTSTRAP VALIDATION ANIMATION ==================== */

/* ==================== END - JAVASCRIPT READY ==================== */








