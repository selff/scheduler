
        <div class="col-md-6">
            <p>The first and second rows in the table are services. The first line contains the name of the company, and
                the second line indicates the number of persons participating in the meeting of the company. The first
                three columns in the table are also services.<br>
                At the intersection of the row and column markers indicate if the counterparties must meet, and if you
                want to ask yourself the exact time they enter the marker instead of the exact time of the meeting.</p>
            <form enctype="multipart/form-data" method="POST" action="?run">
                <div class="form-group">
                    <label>Optional input file with dummy table, if this field is empty will be used demo table</label>
                    <input name="userfile" class="form-control" type="file"/>
                </div>
                <div class="form-group">
                    <label>Slot duration in minutes.</label>
                    <input type="text" class="form-control" name="slot" value="30"/>
                </div>
                <div class="form-group">
                    <label>Event Start Time HH:MM</label>
                    <input type="text" class="form-control" name="start" value="12:00"/>
                </div>
                <div class="form-group">
                    <label>End Time events HH:MM</label>
                    <input type="text" class="form-control" name="end" value="15:00"/>
                </div>
                <div class="form-group">
                    <label>Possible pauses separated by commas</label>
                    <input type="text" class="form-control" name="breaks" value="13:00-14:00"/>
                </div>
                <div class="input-group form-group">
                    <span class="input-group-addon">
                        <input type="checkbox" name="fileSave" value="1"/>
                    </span>
                    <label class="form-control">Generate file for download with result</label>
                </div><!-- /input-group -->
                <div class="form-group">
                    <input type="submit" name="submit" value="Submit" class="btn btn-primary"/>
                </div>
            </form>
        </div>
        <div class="col-md-6">
            <p>Example dummy table for input:</p>
            <?=$thetable?>
        </div>
